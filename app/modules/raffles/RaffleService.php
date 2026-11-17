<?php
declare(strict_types=1);

namespace App\Modules\Raffles;

use App\Modules\PaymentMethods\PaymentMethodRepository;
use App\Modules\Prizes\PrizeRepository;
use App\Shared\Audit\AuditService;
use App\Shared\Dates;
use App\Shared\Exceptions\DomainException;
use App\Shared\Ids;
use App\Shared\Money;
use App\Shared\Storage\ImageStorage;
use App\Shared\Validation\FieldRules;
use App\Shared\Validation\Validator;
use PDO;

final class RaffleService
{
    private RaffleRepository $raffles;
    private PrizeRepository $prizes;
    private PaymentMethodRepository $methods;
    private ImageStorage $images;
    private AuditService $audit;
    private PDO $pdo;

    public function __construct(
        RaffleRepository $raffles,
        PrizeRepository $prizes,
        PaymentMethodRepository $methods,
        ImageStorage $images,
        AuditService $audit,
        PDO $pdo
    ) {
        $this->raffles = $raffles;
        $this->prizes = $prizes;
        $this->methods = $methods;
        $this->images = $images;
        $this->audit = $audit;
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $search = null, ?string $status = null): array
    {
        return $this->raffles->all($search, $status);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->raffles->find($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function rulesForRaffle(int $id): ?array
    {
        return $this->raffles->rulesForRaffle($id);
    }

    /**
     * @return int[]
     */
    public function prizeIds(int $id): array
    {
        return $this->raffles->prizeIds($id);
    }

    /**
     * @return int[]
     */
    public function paymentMethodIds(int $id): array
    {
        return $this->raffles->paymentMethodIds($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activePrizes(): array
    {
        return $this->prizes->activeAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activePaymentMethods(): array
    {
        return $this->methods->activeAll();
    }

    public function countAll(): int
    {
        return $this->raffles->countAll();
    }

    public function countPublished(): int
    {
        return $this->raffles->countPublished();
    }

    /**
     * @param array<string, mixed> $input
     * @param int[] $prizeIds
     * @param int[] $methodIds
     * @param array<string, mixed>|null $cover
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>, id?: int}
     */
    public function create(
        array $input,
        array $prizeIds,
        array $methodIds,
        string $rulesContent,
        ?array $cover,
        int $actorId
    ): array {
        $data = $this->normalize($input, $prizeIds, $methodIds, $rulesContent);
        $errors = $this->validate($data, null);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newCover = null;

        try {
            $newCover = $this->images->store($cover, 'raffles');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['cover_image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data, $newCover, $actorId);
        $rulesHash = hash('sha256', (string) $data['rules_content']);

        try {
            $this->pdo->beginTransaction();
            $raffleId = $this->raffles->insert($record);
            $rulesId = $this->raffles->createRulesVersion($raffleId, (string) $data['rules_content'], $rulesHash, $actorId);
            $this->raffles->setCurrentRules($raffleId, $rulesId);
            $this->raffles->syncPrizes($raffleId, $data['prize_ids']);
            $this->raffles->syncPaymentMethods($raffleId, $data['method_ids']);

            $this->audit->record('raffle.created', 'raffle', $raffleId, [], [
                'raffle' => $record,
                'prize_ids' => $data['prize_ids'],
                'payment_method_ids' => $data['method_ids'],
                'rules_hash' => $rulesHash,
            ], $actorId);

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newCover);

            throw $exception;
        }

        return ['ok' => true, 'errors' => [], 'data' => $data, 'id' => $raffleId];
    }

    /**
     * @param array<string, mixed> $input
     * @param int[] $prizeIds
     * @param int[] $methodIds
     * @param array<string, mixed>|null $cover
     * @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>}
     */
    public function update(
        int $id,
        array $input,
        array $prizeIds,
        array $methodIds,
        string $rulesContent,
        ?array $cover,
        int $actorId
    ): array {
        $existing = $this->raffles->find($id);

        if ($existing === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if ($existing['status'] !== 'draft') {
            throw new DomainException('Solo se puede editar un sorteo en borrador.');
        }

        $data = $this->normalize($input, $prizeIds, $methodIds, $rulesContent);
        $errors = $this->validate($data, $id);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'data' => $data];
        }

        $newCover = null;

        try {
            $newCover = $this->images->store($cover, 'raffles');
        } catch (\Throwable $exception) {
            return ['ok' => false, 'errors' => ['cover_image' => $exception->getMessage()], 'data' => $data];
        }

        $record = $this->toRecord($data, $newCover ?? $existing['cover_image_path'], (int) $existing['created_by']);
        $rulesHash = hash('sha256', (string) $data['rules_content']);
        $rules = $this->raffles->rulesForRaffle($id);

        try {
            $this->pdo->beginTransaction();
            $this->raffles->update($id, $record);

            if ($rules === null) {
                $rulesId = $this->raffles->createRulesVersion($id, (string) $data['rules_content'], $rulesHash, $actorId);
                $this->raffles->setCurrentRules($id, $rulesId);
            } elseif ($rules['status'] === 'draft') {
                $this->raffles->updateRules((int) $rules['id'], (string) $data['rules_content'], $rulesHash);
            } else {
                $rulesId = $this->raffles->createRulesVersion($id, (string) $data['rules_content'], $rulesHash, $actorId);
                $this->raffles->setCurrentRules($id, $rulesId);
            }

            $this->raffles->syncPrizes($id, $data['prize_ids']);
            $this->raffles->syncPaymentMethods($id, $data['method_ids']);

            $this->audit->record('raffle.updated', 'raffle', $id, $existing, [
                'raffle' => $record,
                'prize_ids' => $data['prize_ids'],
                'payment_method_ids' => $data['method_ids'],
                'rules_hash' => $rulesHash,
            ], $actorId);

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newCover);

            throw $exception;
        }

        if ($newCover !== null && $existing['cover_image_path'] !== $newCover) {
            $this->images->delete($existing['cover_image_path']);
        }

        return ['ok' => true, 'errors' => [], 'data' => $data];
    }

    /**
     * Actualiza solo la portada del sorteo, sin tocar fechas, premios ni bases.
     * Se permite incluso cuando el sorteo ya está publicado.
     *
     * @param array<string, mixed>|null $cover
     */
    public function updateCover(int $id, ?array $cover, int $actorId): void
    {
        $raffle = $this->raffles->find($id);

        if ($raffle === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if ($cover === null || (int) ($cover['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new DomainException('Selecciona una imagen de portada.');
        }

        try {
            $newCover = $this->images->store($cover, 'raffles');
        } catch (\Throwable $exception) {
            throw new DomainException($exception->getMessage());
        }

        if ($newCover === null) {
            throw new DomainException('No se pudo subir la portada.');
        }

        try {
            $this->pdo->beginTransaction();
            $this->raffles->updateCover($id, $newCover);
            $this->audit->record(
                'raffle.cover_updated',
                'raffle',
                $id,
                ['cover_image_path' => $raffle['cover_image_path']],
                ['cover_image_path' => $newCover],
                $actorId
            );
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->images->delete($newCover);

            throw $exception;
        }

        if ($newCover !== null && $raffle['cover_image_path'] !== $newCover) {
            $this->images->delete($raffle['cover_image_path']);
        }
    }

    /**
     * @param array<string, bool> $checklist
     * @return array{ok: bool, errors: array<string, string>}
     */
    public function publish(int $id, array $checklist, int $actorId): array
    {
        $raffle = $this->raffles->find($id);

        if ($raffle === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if ($raffle['status'] !== 'draft') {
            throw new DomainException('Solo se puede publicar un sorteo en borrador.');
        }

        $prizeIds = $this->raffles->prizeIds($id);
        $methodIds = $this->raffles->paymentMethodIds($id);
        $rules = $this->raffles->rulesForRaffle($id);

        $data = $this->toFormData($raffle, $rules, $prizeIds, $methodIds);
        $errors = $this->validate($data, $id);

        $requiredChecklist = [
            'legal_validated',
            'bases_approved',
            'privacy_approved',
            'claims_available',
            'authorization_checked',
        ];

        $missing = [];
        foreach ($requiredChecklist as $key) {
            if (empty($checklist[$key])) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            $errors['legal'] = 'Debes confirmar todos los puntos del checklist legal antes de publicar.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $legalChecklist = [];
        foreach ($requiredChecklist as $key) {
            $legalChecklist[$key] = true;
        }

        $this->pdo->beginTransaction();

        try {
            $now = Dates::now();
            $this->raffles->updateStatus($id, 'published', [
                'published_at' => $now,
                'legal_checklist' => json_encode($legalChecklist, JSON_UNESCAPED_UNICODE),
                'legal_confirmed_at' => $now,
                'legal_confirmed_by' => $actorId,
            ]);
            $this->raffles->publishRules((int) $rules['id']);
            $this->audit->record('raffle.published', 'raffle', $id, ['status' => 'draft'], [
                'status' => 'published',
                'legal_checklist' => $legalChecklist,
            ], $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return ['ok' => true, 'errors' => []];
    }

    public function close(int $id, int $actorId): void
    {
        $raffle = $this->raffles->find($id);

        if ($raffle === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if (!in_array($raffle['status'], ['published', 'paused'], true)) {
            throw new DomainException('Solo se puede cerrar un sorteo publicado o pausado.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->raffles->updateStatus($id, 'sales_closed', ['closed_at' => Dates::now()]);
            $this->audit->record('raffle.sales_closed', 'raffle', $id, ['status' => $raffle['status']], ['status' => 'sales_closed'], $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function cancel(int $id, string $reason, int $actorId): void
    {
        $raffle = $this->raffles->find($id);

        if ($raffle === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if (!in_array($raffle['status'], ['draft', 'published', 'paused'], true)) {
            throw new DomainException('Este sorteo ya no se puede cancelar.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('Indica el motivo de la cancelación para dejarlo en la auditoría.');
        }

        if (mb_strlen($reason) < 5) {
            throw new DomainException('El motivo de la cancelación es demasiado corto.');
        }

        if (mb_strlen($reason) > 255) {
            throw new DomainException('El motivo de la cancelación no puede superar los 255 caracteres.');
        }

        if ($this->raffles->countOrders($id) > 0 || $this->raffles->countTickets($id) > 0 || $this->raffles->countNumbers($id) > 0) {
            throw new DomainException('No puedes cancelar un sorteo con participaciones. Gestiona primero los tickets y pagos.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->raffles->updateStatus($id, 'cancelled', ['closed_at' => Dates::now()]);
            $this->audit->record('raffle.cancelled', 'raffle', $id, ['status' => $raffle['status']], [
                'status' => 'cancelled',
                'reason' => $reason,
            ], $actorId);
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
        $raffle = $this->raffles->find($id);

        if ($raffle === null) {
            throw new DomainException('El sorteo no existe.');
        }

        if ($raffle['status'] !== 'draft') {
            throw new DomainException('Solo se puede eliminar un sorteo en borrador.');
        }

        if ($this->raffles->countOrders($id) > 0 || $this->raffles->countTickets($id) > 0 || $this->raffles->countNumbers($id) > 0) {
            throw new DomainException('No puedes eliminar un sorteo con participaciones. Cancélalo con evidencia.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->raffles->clearCurrentRules($id);
            $this->raffles->syncPrizes($id, []);
            $this->raffles->syncPaymentMethods($id, []);
            $this->raffles->deleteRulesForRaffle($id);
            $this->raffles->delete($id);
            $this->audit->record('raffle.deleted', 'raffle', $id, $raffle, [], $actorId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        $this->images->delete($raffle['cover_image_path']);
    }

    /**
     * @param array<string, mixed> $raffle
     * @param array<string, mixed>|null $rules
     * @param int[] $prizeIds
     * @param int[] $methodIds
     * @return array<string, mixed>
     */
    public function toFormData(array $raffle, ?array $rules, array $prizeIds, array $methodIds): array
    {
        return [
            'name' => (string) $raffle['name'],
            'slug' => (string) $raffle['slug'],
            'summary' => (string) ($raffle['summary'] ?? ''),
            'description' => (string) ($raffle['description'] ?? ''),
            'price' => Money::input((int) $raffle['price_cents']),
            'total_tickets' => (string) $raffle['total_tickets'],
            'max_tickets_per_participant' => (string) $raffle['max_tickets_per_participant'],
            'min_age' => (string) $raffle['min_age'],
            'sales_start' => Dates::toInput($raffle['sales_start_at']),
            'sales_end' => Dates::toInput($raffle['sales_end_at']),
            'draw_at' => Dates::toInput($raffle['draw_at']),
            'claim_deadline' => Dates::toInput($raffle['claim_deadline_at']),
            'is_featured' => (bool) $raffle['is_featured'],
            'featured_order' => $raffle['featured_order'] !== null ? (string) $raffle['featured_order'] : '',
            'rules_content' => $rules !== null ? (string) $rules['content'] : '',
            'prize_ids' => $prizeIds,
            'method_ids' => $methodIds,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param int[] $prizeIds
     * @param int[] $methodIds
     * @return array<string, mixed>
     */
    private function normalize(array $input, array $prizeIds, array $methodIds, string $rulesContent): array
    {
        $name = FieldRules::normalize('display_name', (string) ($input['name'] ?? ''));
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = $slugInput !== '' ? Ids::slug($slugInput) : Ids::slug($name);
        $isFeatured = !empty($input['is_featured']);

        return [
            'name' => $name,
            'slug' => $slug,
            'summary' => preg_replace('/\s+/', ' ', FieldRules::normalizeText((string) ($input['summary'] ?? ''))) ?? '',
            'description' => FieldRules::normalizeText((string) ($input['description'] ?? '')),
            'price' => FieldRules::normalize('decimal', (string) ($input['price'] ?? '')),
            'total_tickets' => FieldRules::normalize('integer', (string) ($input['total_tickets'] ?? '')),
            'max_tickets_per_participant' => FieldRules::normalize('integer', (string) ($input['max_tickets_per_participant'] ?? '')),
            'min_age' => FieldRules::normalize('integer', (string) ($input['min_age'] ?? '18')),
            'sales_start' => trim((string) ($input['sales_start'] ?? '')),
            'sales_end' => trim((string) ($input['sales_end'] ?? '')),
            'draw_at' => trim((string) ($input['draw_at'] ?? '')),
            'claim_deadline' => trim((string) ($input['claim_deadline'] ?? '')),
            'is_featured' => $isFeatured,
            'featured_order' => $isFeatured ? FieldRules::normalize('integer', (string) ($input['featured_order'] ?? '')) : '',
            'rules_content' => FieldRules::normalizeText($rulesContent),
            'prize_ids' => $this->normalizeIds($prizeIds),
            'method_ids' => $this->normalizeIds($methodIds),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validate(array $data, ?int $id): array
    {
        $errors = [];

        if ($message = Validator::required((string) $data['name'], 'El nombre del sorteo es obligatorio.')) {
            $errors['name'] = $message;
        } elseif ($message = Validator::minLength((string) $data['name'], 3, 'El nombre debe tener al menos 3 caracteres.')) {
            $errors['name'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['name'], 160, 'El nombre no puede superar los 160 caracteres.')) {
            $errors['name'] = $message;
        } elseif (!FieldRules::hasLetter((string) $data['name'])) {
            $errors['name'] = 'El nombre debe incluir al menos una letra.';
        } elseif (!FieldRules::matches('display_name', (string) $data['name'])) {
            $errors['name'] = FieldRules::message('display_name');
        }

        if ($message = Validator::required((string) $data['slug'], 'El slug es obligatorio.')) {
            $errors['slug'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['slug'], 120, 'El slug no puede superar los 120 caracteres.')) {
            $errors['slug'] = $message;
        } elseif (!FieldRules::matches('slug', (string) $data['slug'])) {
            $errors['slug'] = FieldRules::message('slug');
        } elseif ($this->raffles->findBySlug((string) $data['slug'], $id) !== null) {
            $errors['slug'] = 'Ese slug ya está en uso por otro sorteo.';
        }

        if ($message = Validator::maxLength((string) $data['summary'], 255, 'El resumen no puede superar los 255 caracteres.')) {
            $errors['summary'] = $message;
        }

        if ($message = Validator::maxLength((string) $data['description'], 20000, 'La descripción no puede superar los 20000 caracteres.')) {
            $errors['description'] = $message;
        }

        if (!FieldRules::matches('decimal', (string) $data['price'])) {
            $errors['price'] = FieldRules::message('decimal');
        } else {
            $priceCents = Money::toCents((string) $data['price']);

            if ($priceCents === null) {
                $errors['price'] = 'Ingresa un precio válido.';
            } elseif ($priceCents <= 0) {
                $errors['price'] = 'El precio debe ser mayor a cero.';
            }
        }

        if (!FieldRules::matches('integer', (string) $data['total_tickets'])) {
            $errors['total_tickets'] = FieldRules::message('integer');
            $totalTickets = false;
        } else {
            $totalTickets = filter_var($data['total_tickets'], FILTER_VALIDATE_INT);

            if ($totalTickets === false) {
                $errors['total_tickets'] = 'Ingresa un número entero de cupos.';
            } elseif ($totalTickets < 1 || $totalTickets > 1000000) {
                $errors['total_tickets'] = 'Los cupos deben estar entre 1 y 1,000,000.';
            }
        }

        if (!FieldRules::matches('integer', (string) $data['max_tickets_per_participant'])) {
            $errors['max_tickets_per_participant'] = FieldRules::message('integer');
            $maxPerPerson = false;
        } else {
            $maxPerPerson = filter_var($data['max_tickets_per_participant'], FILTER_VALIDATE_INT);

            if ($maxPerPerson === false) {
                $errors['max_tickets_per_participant'] = 'Ingresa un número entero.';
            } elseif ($maxPerPerson < 1) {
                $errors['max_tickets_per_participant'] = 'El máximo por persona debe ser al menos 1.';
            } elseif ($totalTickets !== false && $maxPerPerson > $totalTickets) {
                $errors['max_tickets_per_participant'] = 'El máximo por persona no puede superar los cupos.';
            }
        }

        if (!FieldRules::matches('integer', (string) $data['min_age'])) {
            $errors['min_age'] = FieldRules::message('integer');
            $minAge = false;
        } else {
            $minAge = filter_var($data['min_age'], FILTER_VALIDATE_INT);

            if ($minAge === false) {
                $errors['min_age'] = 'Ingresa una edad mínima válida.';
            } elseif ($minAge < 18 || $minAge > 100) {
                $errors['min_age'] = 'La edad mínima debe estar entre 18 y 100 años.';
            }
        }

        $start = Dates::fromInput((string) $data['sales_start']);
        $end = Dates::fromInput((string) $data['sales_end']);
        $draw = Dates::fromInput((string) $data['draw_at']);
        $claim = Dates::fromInput((string) $data['claim_deadline']);

        if ($start === null) {
            $errors['sales_start'] = 'Indica una fecha de inicio válida.';
        }
        if ($end === null) {
            $errors['sales_end'] = 'Indica una fecha de cierre válida.';
        }
        if ($draw === null) {
            $errors['draw_at'] = 'Indica una fecha de sorteo válida.';
        }
        if ($claim === null) {
            $errors['claim_deadline'] = 'Indica una fecha límite de reclamo válida.';
        }

        if ($start !== null && $end !== null && strtotime($start) >= strtotime($end)) {
            $errors['sales_end'] = 'El cierre debe ser posterior al inicio de ventas.';
        }
        if ($end !== null && $draw !== null && strtotime($draw) < strtotime($end)) {
            $errors['draw_at'] = 'El sorteo debe ser posterior o igual al cierre de ventas.';
        }
        if ($draw !== null && $claim !== null && strtotime($claim) <= strtotime($draw)) {
            $errors['claim_deadline'] = 'El plazo de reclamo debe ser posterior al sorteo.';
        }

        foreach (['sales_start' => $start, 'sales_end' => $end, 'draw_at' => $draw, 'claim_deadline' => $claim] as $field => $value) {
            if ($value === null) {
                continue;
            }

            $year = (int) substr($value, 0, 4);

            if ($year < 2020 || $year > 2100) {
                $errors[$field] = 'La fecha debe estar entre los años 2020 y 2100.';
            }
        }

        $activePrizes = array_map('intval', array_column($this->prizes->activeAll(), 'id'));
        if ($data['prize_ids'] === []) {
            $errors['prize_ids'] = 'Selecciona al menos un premio activo.';
        } else {
            foreach ($data['prize_ids'] as $prizeId) {
                if (!in_array((int) $prizeId, $activePrizes, true)) {
                    $errors['prize_ids'] = 'Uno de los premios seleccionados no está activo.';
                    break;
                }
            }
        }

        $activeMethods = array_map('intval', array_column($this->methods->activeAll(), 'id'));
        if ($data['method_ids'] === []) {
            $errors['method_ids'] = 'Selecciona al menos un método de pago activo.';
        } else {
            foreach ($data['method_ids'] as $methodId) {
                if (!in_array((int) $methodId, $activeMethods, true)) {
                    $errors['method_ids'] = 'Uno de los métodos seleccionados no está activo.';
                    break;
                }
            }
        }

        if ($message = Validator::required((string) $data['rules_content'], 'Las bases y condiciones son obligatorias.')) {
            $errors['rules_content'] = $message;
        } elseif ($message = Validator::minLength((string) $data['rules_content'], 20, 'Las bases deben tener al menos 20 caracteres.')) {
            $errors['rules_content'] = $message;
        } elseif ($message = Validator::maxLength((string) $data['rules_content'], 50000, 'Las bases no pueden superar los 50000 caracteres.')) {
            $errors['rules_content'] = $message;
        }

        if ($data['is_featured']) {
            if (!FieldRules::matches('integer', (string) $data['featured_order'])) {
                $errors['featured_order'] = FieldRules::message('integer');
            } else {
                $featuredOrder = filter_var($data['featured_order'], FILTER_VALIDATE_INT);

                if ($featuredOrder === false || $featuredOrder < 1 || $featuredOrder > 3) {
                    $errors['featured_order'] = 'El orden destacado debe estar entre 1 y 3.';
                } elseif ($this->raffles->findByFeaturedOrder((int) $featuredOrder, $id) !== null) {
                    $errors['featured_order'] = 'Ese orden destacado ya está en uso por otro sorteo.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function toRecord(array $data, ?string $coverImage, ?int $actorId): array
    {
        return [
            'public_code' => Ids::publicCode(),
            'slug' => $data['slug'],
            'name' => $data['name'],
            'summary' => $data['summary'] !== '' ? $data['summary'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'status' => 'draft',
            'price_cents' => (int) Money::toCents((string) $data['price']),
            'currency' => 'PEN',
            'total_tickets' => (int) $data['total_tickets'],
            'max_tickets_per_participant' => (int) $data['max_tickets_per_participant'],
            'min_age' => (int) $data['min_age'],
            'sales_start_at' => Dates::fromInput((string) $data['sales_start']),
            'sales_end_at' => Dates::fromInput((string) $data['sales_end']),
            'draw_at' => Dates::fromInput((string) $data['draw_at']),
            'claim_deadline_at' => Dates::fromInput((string) $data['claim_deadline']),
            'is_featured' => $data['is_featured'] ? 1 : 0,
            'featured_order' => $data['is_featured'] ? (int) $data['featured_order'] : null,
            'cover_image_path' => $coverImage,
            'created_by' => $actorId,
        ];
    }

    /**
     * @param array<int, mixed> $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }
}
