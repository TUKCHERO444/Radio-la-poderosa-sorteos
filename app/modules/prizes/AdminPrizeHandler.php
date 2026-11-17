<?php
declare(strict_types=1);

namespace App\Modules\Prizes;

use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Input;
use App\Shared\Money;

final class AdminPrizeHandler
{
    private PrizeService $service;

    public function __construct()
    {
        $this->service = admin_prize_service();
    }

    public function index(): void
    {
        $search = Input::get('q');
        $status = Input::get('status');

        \admin_view('prizes/index', [
            'title' => 'Premios',
            'active' => 'prizes',
            'prizes' => $this->service->all($search !== '' ? $search : null, $status !== '' ? $status : null),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', null, $this->defaults(), []);
    }

    public function store(): void
    {
        \admin_verify_csrf();

        $input = $this->input();
        $image = Input::file('image');

        try {
            $result = $this->service->create($input, $image, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('create', null, $result['data'], $result['errors']);
                return;
            }

            \admin_flash('success', 'Premio creado correctamente.');
            \admin_redirect('/admin/prizes');
        } catch (DomainException $exception) {
            $this->renderForm('create', null, $input + $this->defaults(), ['general' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            error_log('Prize create: ' . $exception->getMessage());
            $this->renderForm('create', null, $input + $this->defaults(), ['general' => 'No se pudo crear el premio.']);
        }
    }

    public function edit(int $id): void
    {
        $prize = $this->service->find($id);

        if ($prize === null) {
            \admin_abort(404, 'El premio solicitado no existe.');
        }

        $this->renderForm('edit', $prize, $this->defaults($prize), [], $id);
    }

    public function update(int $id): void
    {
        \admin_verify_csrf();

        $prize = $this->service->find($id);

        if ($prize === null) {
            \admin_abort(404, 'El premio solicitado no existe.');
        }

        $input = $this->input();
        $image = Input::file('image');

        try {
            $result = $this->service->update($id, $input, $image, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('edit', $prize, $result['data'], $result['errors'], $id);
                return;
            }

            \admin_flash('success', 'Premio actualizado correctamente.');
            \admin_redirect('/admin/prizes');
        } catch (DomainException $exception) {
            $this->renderForm('edit', $prize, $input + $this->defaults($prize), ['general' => $exception->getMessage()], $id);
        } catch (\Throwable $exception) {
            error_log('Prize update: ' . $exception->getMessage());
            $this->renderForm('edit', $prize, $input + $this->defaults($prize), ['general' => 'No se pudo actualizar el premio.'], $id);
        }
    }

    public function toggle(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->toggle($id, Input::post('active') === '1', (int) \admin_current_user()['id']);
            \admin_flash('success', 'Estado del premio actualizado.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Prize toggle: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo cambiar el estado del premio.');
        }

        \admin_redirect('/admin/prizes');
    }

    public function delete(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->delete($id, (int) \admin_current_user()['id']);
            \admin_flash('success', 'Premio eliminado correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Prize delete: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo eliminar el premio.');
        }

        \admin_redirect('/admin/prizes');
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'name' => Input::post('name'),
            'prize_type' => Input::post('prize_type', 'product'),
            'estimated_value' => Input::post('estimated_value'),
            'delivery_method' => Input::post('delivery_method'),
            'description' => Input::post('description'),
            'is_active' => Input::bool('is_active'),
        ];
    }

    /**
     * @param array<string, mixed>|null $prize
     * @return array<string, mixed>
     */
    private function defaults(?array $prize = null): array
    {
        if ($prize === null) {
            return [
                'name' => '',
                'prize_type' => 'product',
                'estimated_value' => '',
                'delivery_method' => '',
                'description' => '',
                'is_active' => true,
            ];
        }

        return [
            'name' => (string) $prize['name'],
            'prize_type' => (string) $prize['prize_type'],
            'estimated_value' => $prize['estimated_value_cents'] !== null ? Money::input((int) $prize['estimated_value_cents']) : '',
            'delivery_method' => (string) ($prize['delivery_method'] ?? ''),
            'description' => (string) ($prize['description'] ?? ''),
            'is_active' => (bool) $prize['is_active'],
        ];
    }

    /**
     * @param array<string, mixed>|null $prize
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     */
    private function renderForm(string $mode, ?array $prize, array $old, array $errors, int $id = 0): void
    {
        \admin_view('prizes/form', [
            'title' => $mode === 'edit' ? 'Editar premio' : 'Nuevo premio',
            'active' => 'prizes',
            'mode' => $mode,
            'prize' => $prize,
            'old' => $old,
            'errors' => $errors,
            'action' => $mode === 'edit' ? '/admin/prizes/' . $id : '/admin/prizes',
        ]);
    }
}
