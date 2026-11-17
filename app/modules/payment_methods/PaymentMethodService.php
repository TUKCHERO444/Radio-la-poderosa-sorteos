<?php
declare(strict_types=1);

namespace App\Modules\PaymentMethods;

use App\Shared\Audit\AuditService;
use App\Shared\Exceptions\DomainException;
use App\Shared\Storage\ImageStorage;
use App\Shared\Validation\FieldRules;
use App\Shared\Validation\Validator;
use PDO;

final class PaymentMethodService
{
    private PaymentMethodRepository $methods;
    private ImageStorage $images;
    private AuditService $audit;
    private PDO $pdo;

    public function __construct(
        PaymentMethodRepository $methods,
        ImageStorage $images,
        AuditService $audit,
        PDO $pdo
    ) {
        $this->methods = $methods;
        $this->images = $images;
        $this->audit = $audit;
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $search = null, ?string $type = null, ?string $status = null): array
    {
        return $this->methods->all($search, $type, $status);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->methods->find($id);
    }

    public function countAll(): int
    {
        return $this->methods->countAll();
    }

    public function countAssigned(int $id): int
    {
        return $this->methods->countAssigned($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeAll(): array
    {
        return $this->methods->activeAll();
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $qr
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>, id?: int}
     */
    public function create(array $input, ?array $qr, int $actorId): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newQr = null;

        try {
            $newQr = $this->images->store($qr, 'payment-methods');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['qr_image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data);
        $record['qr_image_path'] = $newQr;

        try {
            $this->pdo->beginTransaction();
            $id = $this->methods->insert($record);
            $this->audit->record('payment_method.created', 'payment_method', $id, [], $record, $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newQr);

            throw $exception;
        }

        return ['ok' => true, 'errors' => [], 'data' => $data, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $qr
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>}
     */
    public function update(int $id, array $input, ?array $qr, int $actorId): array
    {
        $existing = $this->methods->find($id);

        if ($existing === null) {
            throw new DomainException('El método de pago no existe.');
        }

        $data = $this->normalize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newQr = null;

        try {
            $newQr = $this->images->store($qr, 'payment-methods');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['qr_image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data);
        $record['qr_image_path'] = $newQr ?? $existing['qr_image_path'];

        try {
            $this->pdo->beginTransaction();
            $this->methods->update($id, $record);
            $this->audit->record('payment_method.updated', 'payment_method', $id, $existing, $record, $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newQr);

            throw $exception;
        }

        if ($newQr !== null && $existing['qr_image_path'] !== $newQr) {
            $this->images->delete($existing['qr_image_path']);
        }

        return ['ok' => true, 'errors' => [], 'data' => $data];
    }

    public function toggle(int $id, bool $active, int $actorId): void
    {
        $existing = $this->methods->find($id);

        if ($existing === null) {
            throw new DomainException('El método de pago no existe.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->methods->toggle($id, $active);
            $this->audit->record(
                $active ? 'payment_method.activated' : 'payment_method.deactivated',
                'payment_method',
                $id,
                ['is_active' => (int) $existing['is_active']],
                ['is_active' => $active ? 1 : 0],
                $actorId
            );
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id, int $actorId): void
    {
        $existing = $this->methods->find($id);

        if ($existing === null) {
            throw new DomainException('El método de pago no existe.');
        }

        if ($this->methods->countAssigned($id) > 0) {
            throw new DomainException('No puedes eliminar un método asignado a un sorteo. Desactívalo o quítalo del sorteo.');
        }

        if ($this->methods->countPayments($id) > 0) {
            throw new DomainException('No puedes eliminar un método con pagos registrados. Desactívalo.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->methods->delete($id);
            $this->audit->record('payment_method.deleted', 'payment_method', $id, $existing, [], $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        $this->images->delete($existing['qr_image_path']);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        return [
            'name' => FieldRules::normalize('display_name', (string) ($input['name'] ?? '')),
            'type' => (string) ($input['type'] ?? 'wallet'),
            'provider' => (string) ($input['provider'] ?? 'yape'),
            'holder_name' => FieldRules::normalize('person_name', (string) ($input['holder_name'] ?? '')),
            'holder_document' => FieldRules::normalize('document', (string) ($input['holder_document'] ?? '')),
            'phone' => FieldRules::normalize('phone', (string) ($input['phone'] ?? '')),
            'bank_name' => FieldRules::normalize('display_name', (string) ($input['bank_name'] ?? '')),
            'account_number' => FieldRules::normalize('account', (string) ($input['account_number'] ?? '')),
            'cci' => FieldRules::normalize('cci', (string) ($input['cci'] ?? '')),
            'contact_phone' => FieldRules::normalize('phone', (string) ($input['contact_phone'] ?? '')),
            'instructions' => FieldRules::normalizeText((string) ($input['instructions'] ?? '')),
            'is_active' => !empty($input['is_active']),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ($message = Validator::required((string) $data['name'], 'El nombre del método es obligatorio.')) {
            $errors['name'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['name'], 120, 'El nombre no puede superar los 120 caracteres.')) {
            $errors['name'] = $message;
        } elseif (!FieldRules::hasLetter((string) $data['name'])) {
            $errors['name'] = 'El nombre debe incluir al menos una letra.';
        } elseif (!FieldRules::matches('display_name', (string) $data['name'])) {
            $errors['name'] = FieldRules::message('display_name');
        }

        if ($message = Validator::enum((string) $data['type'], ['wallet', 'bank'], 'El tipo debe ser billetera digital o cuenta bancaria.')) {
            $errors['type'] = $message;
        }

        if ($message = Validator::enum((string) $data['provider'], ['yape', 'plin', 'bcp', 'other'], 'El proveedor no es válido.')) {
            $errors['provider'] = $message;
        }

        if ($message = Validator::required((string) $data['holder_name'], 'El titular es obligatorio.')) {
            $errors['holder_name'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['holder_name'], 160, 'El titular no puede superar los 160 caracteres.')) {
            $errors['holder_name'] = $message;
        } elseif (FieldRules::letterCount((string) $data['holder_name']) < 2) {
            $errors['holder_name'] = 'El titular debe incluir al menos dos letras.';
        } elseif (!FieldRules::matches('person_name', (string) $data['holder_name'])) {
            $errors['holder_name'] = FieldRules::message('person_name');
        }

        if ($data['holder_document'] !== '' && !FieldRules::matches('document', (string) $data['holder_document'])) {
            $errors['holder_document'] = FieldRules::message('document');
        }

        if ($message = Validator::required((string) $data['contact_phone'], 'El teléfono de contacto para sobrepagos es obligatorio.')) {
            $errors['contact_phone'] = $message;
        } elseif (!FieldRules::matches('phone', (string) $data['contact_phone'])) {
            $errors['contact_phone'] = FieldRules::message('phone');
        }

        if ($data['type'] === 'wallet') {
            if ($message = Validator::required((string) $data['phone'], 'El teléfono de la billetera es obligatorio.')) {
                $errors['phone'] = $message;
            } elseif (!FieldRules::matches('phone', (string) $data['phone'])) {
                $errors['phone'] = FieldRules::message('phone');
            }
        }

        if ($data['type'] === 'bank') {
            if ($message = Validator::required((string) $data['bank_name'], 'El banco es obligatorio.')) {
                $errors['bank_name'] = $message;
            } elseif ($message = Validator::maxLength((string) $data['bank_name'], 80, 'El banco no puede superar los 80 caracteres.')) {
                $errors['bank_name'] = $message;
            } elseif (!FieldRules::matches('display_name', (string) $data['bank_name'])) {
                $errors['bank_name'] = 'Usa solo letras, números y signos básicos en el nombre del banco.';
            }

            if ($message = Validator::required((string) $data['account_number'], 'La cuenta o tarjeta es obligatoria.')) {
                $errors['account_number'] = $message;
            } elseif (!FieldRules::matches('account', (string) $data['account_number'])) {
                $errors['account_number'] = FieldRules::message('account');
            }

            if ($data['cci'] !== '' && !FieldRules::matches('cci', (string) $data['cci'])) {
                $errors['cci'] = FieldRules::message('cci');
            }
        }

        if ($message = Validator::maxLength((string) $data['instructions'], 2000, 'Las instrucciones no pueden superar los 2000 caracteres.')) {
            $errors['instructions'] = $message;
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function toRecord(array $data): array
    {
        $isWallet = $data['type'] === 'wallet';

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'provider' => $data['provider'],
            'holder_name' => $data['holder_name'],
            'holder_document' => $data['holder_document'] !== '' ? $data['holder_document'] : null,
            'phone' => $isWallet ? ($data['phone'] !== '' ? $data['phone'] : null) : null,
            'bank_name' => $isWallet ? null : ($data['bank_name'] !== '' ? $data['bank_name'] : null),
            'account_number' => $isWallet ? null : ($data['account_number'] !== '' ? $data['account_number'] : null),
            'cci' => $isWallet ? null : ($data['cci'] !== '' ? $data['cci'] : null),
            'contact_phone' => $data['contact_phone'] !== '' ? $data['contact_phone'] : null,
            'instructions' => $data['instructions'] !== '' ? $data['instructions'] : null,
            'is_active' => $data['is_active'] ? 1 : 0,
        ];
    }
}
