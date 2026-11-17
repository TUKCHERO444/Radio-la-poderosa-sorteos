<?php
declare(strict_types=1);

namespace App\Modules\Raffles;

use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Input;

final class AdminRaffleHandler
{
    private RaffleService $service;

    public function __construct()
    {
        $this->service = admin_raffle_service();
    }

    public function index(): void
    {
        $search = Input::get('q');
        $status = Input::get('status');

        \admin_view('raffles/index', [
            'title' => 'Sorteos',
            'active' => 'raffles',
            'raffles' => $this->service->all($search !== '' ? $search : null, $status !== '' ? $status : null),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', 0, $this->defaults(), []);
    }

    public function store(): void
    {
        \admin_verify_csrf();

        $input = $this->input();
        $prizeIds = $this->prizeIdsFromInput();
        $methodIds = $this->ids('method_ids');
        $rules = Input::rawPost('rules_content');
        $cover = Input::file('cover_image');
        $old = $this->formData($input, $prizeIds, $methodIds, $rules);

        try {
            $result = $this->service->create($input, $prizeIds, $methodIds, $rules, $cover, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('create', 0, $result['data'], $result['errors']);
                return;
            }

            \admin_flash('success', 'Sorteo creado en borrador. Revisa los datos y publica cuando esté listo.');
            \admin_redirect('/admin/raffles/' . (int) $result['id'] . '/edit');
        } catch (DomainException $exception) {
            $this->renderForm('create', 0, $old, ['general' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            error_log('Raffle create: ' . $exception->getMessage());
            $this->renderForm('create', 0, $old, ['general' => 'No se pudo crear el sorteo.']);
        }
    }

    public function edit(int $id): void
    {
        $raffle = $this->service->find($id);

        if ($raffle === null) {
            \admin_abort(404, 'El sorteo solicitado no existe.');
        }

        $rules = $this->service->rulesForRaffle($id);
        $prizeIds = $this->service->prizeIds($id);
        $methodIds = $this->service->paymentMethodIds($id);
        $old = $this->service->toFormData($raffle, $rules, $prizeIds, $methodIds);

        $this->renderForm('edit', $id, $old, [], $raffle, $rules, $raffle['status'] !== 'draft');
    }

    public function update(int $id): void
    {
        \admin_verify_csrf();

        $raffle = $this->service->find($id);

        if ($raffle === null) {
            \admin_abort(404, 'El sorteo solicitado no existe.');
        }

        if ($raffle['status'] !== 'draft') {
            \admin_flash('error', 'Solo se puede editar un sorteo en borrador.');
            \admin_redirect('/admin/raffles/' . $id . '/edit');
        }

        $input = $this->input();
        $prizeIds = $this->prizeIdsFromInput();
        $methodIds = $this->ids('method_ids');
        $rulesContent = Input::rawPost('rules_content');
        $cover = Input::file('cover_image');
        $old = $this->formData($input, $prizeIds, $methodIds, $rulesContent);
        $currentRules = $this->service->rulesForRaffle($id);

        try {
            $result = $this->service->update($id, $input, $prizeIds, $methodIds, $rulesContent, $cover, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('edit', $id, $result['data'], $result['errors'], $raffle, $currentRules, false);
                return;
            }

            \admin_flash('success', 'Sorteo actualizado correctamente.');
            \admin_redirect('/admin/raffles/' . $id . '/edit');
        } catch (DomainException $exception) {
            $this->renderForm('edit', $id, $old, ['general' => $exception->getMessage()], $raffle, $currentRules, false);
        } catch (\Throwable $exception) {
            error_log('Raffle update: ' . $exception->getMessage());
            $this->renderForm('edit', $id, $old, ['general' => 'No se pudo actualizar el sorteo.'], $raffle, $currentRules, false);
        }
    }

    public function updateCover(int $id): void
    {
        \admin_verify_csrf();

        $raffle = $this->service->find($id);

        if ($raffle === null) {
            \admin_abort(404, 'El sorteo solicitado no existe.');
        }

        try {
            $this->service->updateCover(
                $id,
                Input::file('cover_image'),
                (int) \admin_current_user()['id']
            );
            \admin_flash('success', 'Portada actualizada correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Raffle cover: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo actualizar la portada.');
        }

        \admin_redirect('/admin/raffles/' . $id . '/edit');
    }

    public function publishForm(int $id): void
    {
        $raffle = $this->service->find($id);

        if ($raffle === null) {
            \admin_abort(404, 'El sorteo solicitado no existe.');
        }

        if ($raffle['status'] !== 'draft') {
            \admin_flash('error', 'Solo se puede publicar un sorteo en borrador.');
            \admin_redirect('/admin/raffles/' . $id . '/edit');
        }

        $this->renderPublish($raffle, []);
    }

    public function publish(int $id): void
    {
        \admin_verify_csrf();

        $raffle = $this->service->find($id);

        if ($raffle === null) {
            \admin_abort(404, 'El sorteo solicitado no existe.');
        }

        $checklist = [
            'legal_validated' => Input::bool('legal_validated'),
            'bases_approved' => Input::bool('bases_approved'),
            'privacy_approved' => Input::bool('privacy_approved'),
            'claims_available' => Input::bool('claims_available'),
            'authorization_checked' => Input::bool('authorization_checked'),
        ];

        try {
            $result = $this->service->publish($id, $checklist, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderPublish($raffle, $result['errors']);
                return;
            }

            \admin_flash('success', 'Sorteo publicado correctamente.');
            \admin_redirect('/admin/raffles');
        } catch (DomainException $exception) {
            $this->renderPublish($raffle, ['general' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            error_log('Raffle publish: ' . $exception->getMessage());
            $this->renderPublish($raffle, ['general' => 'No se pudo publicar el sorteo.']);
        }
    }

    public function close(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->close($id, (int) \admin_current_user()['id']);
            \admin_flash('success', 'Ventas cerradas correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Raffle close: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudieron cerrar las ventas.');
        }

        \admin_redirect('/admin/raffles');
    }

    public function cancel(int $id): void
    {
        \admin_verify_csrf();

        $reason = Input::post('reason');

        try {
            $this->service->cancel($id, $reason, (int) \admin_current_user()['id']);
            \admin_flash('success', 'Sorteo cancelado correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Raffle cancel: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo cancelar el sorteo.');
        }

        \admin_redirect('/admin/raffles');
    }

    public function delete(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->delete($id, (int) \admin_current_user()['id']);
            \admin_flash('success', 'Sorteo eliminado correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Raffle delete: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo eliminar el sorteo.');
        }

        \admin_redirect('/admin/raffles');
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'name' => Input::post('name'),
            'slug' => Input::post('slug'),
            'summary' => Input::post('summary'),
            'description' => Input::post('description'),
            'price' => Input::post('price'),
            'total_tickets' => Input::post('total_tickets'),
            'max_tickets_per_participant' => Input::post('max_tickets_per_participant'),
            'min_age' => Input::post('min_age', '18'),
            'sales_start' => Input::post('sales_start'),
            'sales_end' => Input::post('sales_end'),
            'draw_at' => Input::post('draw_at'),
            'claim_deadline' => Input::post('claim_deadline'),
            'is_featured' => Input::bool('is_featured'),
            'featured_order' => Input::post('featured_order'),
        ];
    }

    /**
     * @return int[]
     */
    private function prizeIdsFromInput(): array
    {
        $primary = (int) Input::post('primary_prize_id');
        $additional = $this->ids('additional_prize_ids');
        $ids = [];

        if ($primary > 0) {
            $ids[$primary] = $primary;
        }

        foreach ($additional as $id) {
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @return int[]
     */
    private function ids(string $key): array
    {
        $values = Input::array($key);
        $ids = [];

        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'summary' => '',
            'description' => '',
            'price' => '',
            'total_tickets' => '',
            'max_tickets_per_participant' => '',
            'min_age' => '18',
            'sales_start' => '',
            'sales_end' => '',
            'draw_at' => '',
            'claim_deadline' => '',
            'is_featured' => false,
            'featured_order' => '',
            'rules_content' => '',
            'prize_ids' => [],
            'method_ids' => [],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param int[] $prizeIds
     * @param int[] $methodIds
     * @return array<string, mixed>
     */
    private function formData(array $input, array $prizeIds, array $methodIds, string $rulesContent): array
    {
        $data = $input + $this->defaults();
        $data['rules_content'] = $rulesContent;
        $data['prize_ids'] = $prizeIds;
        $data['method_ids'] = $methodIds;

        return $data;
    }

    /**
     * @param array<string, mixed>|null $raffle
     * @param array<string, mixed>|null $rules
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     */
    private function renderForm(
        string $mode,
        int $id,
        array $old,
        array $errors,
        ?array $raffle = null,
        ?array $rules = null,
        bool $readOnly = false
    ): void {
        \admin_view('raffles/form', [
            'title' => $mode === 'edit' ? 'Editar sorteo' : 'Nuevo sorteo',
            'active' => 'raffles',
            'mode' => $mode,
            'raffle' => $raffle,
            'rules' => $rules,
            'old' => $old,
            'errors' => $errors,
            'action' => $mode === 'edit' ? '/admin/raffles/' . $id : '/admin/raffles',
            'read_only' => $readOnly,
            'prizes' => $this->service->activePrizes(),
            'methods' => $this->service->activePaymentMethods(),
        ]);
    }

    /**
     * @param array<string, mixed> $raffle
     * @param array<string, string> $errors
     */
    private function renderPublish(array $raffle, array $errors): void
    {
        \admin_view('raffles/publish', [
            'title' => 'Publicar sorteo',
            'active' => 'raffles',
            'raffle' => $raffle,
            'rules' => $this->service->rulesForRaffle((int) $raffle['id']),
            'prize_ids' => $this->service->prizeIds((int) $raffle['id']),
            'method_ids' => $this->service->paymentMethodIds((int) $raffle['id']),
            'prizes' => $this->service->activePrizes(),
            'methods' => $this->service->activePaymentMethods(),
            'errors' => $errors,
        ]);
    }
}
