<?php
declare(strict_types=1);

namespace App\Modules\Prizes;

use App\Shared\Audit\AuditService;
use App\Shared\Exceptions\DomainException;
use App\Shared\Ids;
use App\Shared\Money;
use App\Shared\Storage\ImageStorage;
use App\Shared\Validation\FieldRules;
use App\Shared\Validation\Validator;
use PDO;

final class PrizeService
{
    private PrizeRepository $prizes;
    private ImageStorage $images;
    private AuditService $audit;
    private PDO $pdo;

    public function __construct(
        PrizeRepository $prizes,
        ImageStorage $images,
        AuditService $audit,
        PDO $pdo
    ) {
        $this->prizes = $prizes;
        $this->images = $images;
        $this->audit = $audit;
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $search = null, ?string $status = null): array
    {
        return $this->prizes->all($search, $status);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->prizes->find($id);
    }

    public function countAll(): int
    {
        return $this->prizes->countAll();
    }

    public function countAssigned(int $id): int
    {
        return $this->prizes->countAssigned($id);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $image
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>, id?: int}
     */
    public function create(array $input, ?array $image, int $actorId): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newImage = null;

        try {
            $newImage = $this->images->store($image, 'prizes');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data);
        $record['public_code'] = Ids::publicCode();
        $record['image_path'] = $newImage;

        try {
            $this->pdo->beginTransaction();
            $id = $this->prizes->insert($record);
            $this->audit->record('prize.created', 'prize', $id, [], $record, $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newImage);

            throw $exception;
        }

        return ['ok' => true, 'errors' => [], 'data' => $data, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $image
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>}
     */
    public function update(int $id, array $input, ?array $image, int $actorId): array
    {
        $existing = $this->prizes->find($id);

        if ($existing === null) {
            throw new DomainException('El premio no existe.');
        }

        $data = $this->normalize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newImage = null;

        try {
            $newImage = $this->images->store($image, 'prizes');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data);
        $record['image_path'] = $newImage ?? $existing['image_path'];

        try {
            $this->pdo->beginTransaction();
            $this->prizes->update($id, $record);
            $this->audit->record('prize.updated', 'prize', $id, $existing, $record, $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newImage);

            throw $exception;
        }

        if ($newImage !== null && $existing['image_path'] !== $newImage) {
            $this->images->delete($existing['image_path']);
        }

        return ['ok' => true, 'errors' => [], 'data' => $data];
    }

    public function toggle(int $id, bool $active, int $actorId): void
    {
        $existing = $this->prizes->find($id);

        if ($existing === null) {
            throw new DomainException('El premio no existe.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->prizes->toggle($id, $active);
            $this->audit->record(
                $active ? 'prize.activated' : 'prize.deactivated',
                'prize',
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
        $existing = $this->prizes->find($id);

        if ($existing === null) {
            throw new DomainException('El premio no existe.');
        }

        if ($this->prizes->countAssigned($id) > 0) {
            throw new DomainException('No puedes eliminar un premio asignado a un sorteo. Desactívalo o quítalo del sorteo.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->prizes->delete($id);
            $this->audit->record('prize.deleted', 'prize', $id, $existing, [], $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        $this->images->delete($existing['image_path']);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        return [
            'name' => FieldRules::normalize('display_name', (string) ($input['name'] ?? '')),
            'prize_type' => (string) ($input['prize_type'] ?? 'product'),
            'estimated_value' => FieldRules::normalize('decimal', (string) ($input['estimated_value'] ?? '')),
            'delivery_method' => FieldRules::normalize('display_name', (string) ($input['delivery_method'] ?? '')),
            'description' => FieldRules::normalizeText((string) ($input['description'] ?? '')),
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

        if ($message = Validator::required((string) $data['name'], 'El nombre del premio es obligatorio.')) {
            $errors['name'] = $message;
        } elseif ($message = Validator::minLength((string) $data['name'], 2, 'El nombre debe tener al menos 2 caracteres.')) {
            $errors['name'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['name'], 160, 'El nombre no puede superar los 160 caracteres.')) {
            $errors['name'] = $message;
        } elseif (!FieldRules::hasLetter((string) $data['name'])) {
            $errors['name'] = 'El nombre debe incluir al menos una letra.';
        } elseif (!FieldRules::matches('display_name', (string) $data['name'])) {
            $errors['name'] = FieldRules::message('display_name');
        }

        if ($message = Validator::enum((string) $data['prize_type'], ['product', 'service', 'cash', 'other'], 'El tipo de premio no es válido.')) {
            $errors['prize_type'] = $message;
        }

        if ($data['estimated_value'] !== '') {
            if (!FieldRules::matches('decimal', (string) $data['estimated_value'])) {
                $errors['estimated_value'] = FieldRules::message('decimal');
            } else {
                $cents = Money::toCents((string) $data['estimated_value']);
                if ($cents === null) {
                    $errors['estimated_value'] = 'Ingresa un valor numérico válido.';
                } elseif ($cents < 0) {
                    $errors['estimated_value'] = 'El valor no puede ser negativo.';
                }
            }
        }

        if ($data['delivery_method'] !== '') {
            if ($message = Validator::maxLength((string) $data['delivery_method'], 60, 'El método de entrega no puede superar los 60 caracteres.')) {
                $errors['delivery_method'] = $message;
            } elseif (!FieldRules::matches('display_name', (string) $data['delivery_method'])) {
                $errors['delivery_method'] = 'Usa solo letras, números y signos básicos en el método de entrega.';
            }
        }

        if ($message = Validator::maxLength((string) $data['description'], 5000, 'La descripción no puede superar los 5000 caracteres.')) {
            $errors['description'] = $message;
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function toRecord(array $data): array
    {
        $value = $data['estimated_value'] !== '' ? Money::toCents((string) $data['estimated_value']) : null;

        return [
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'prize_type' => $data['prize_type'],
            'estimated_value_cents' => $value,
            'delivery_method' => $data['delivery_method'] !== '' ? $data['delivery_method'] : null,
            'is_active' => $data['is_active'] ? 1 : 0,
        ];
    }
}
