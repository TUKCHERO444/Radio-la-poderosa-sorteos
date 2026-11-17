<?php
declare(strict_types=1);

namespace App\Modules\PaymentMethods;

use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Input;

final class AdminPaymentMethodHandler
{
    private PaymentMethodService $service;

    public function __construct()
    {
        $this->service = admin_payment_method_service();
    }

    public function index(): void
    {
        $search = Input::get('q');
        $type = Input::get('type');
        $status = Input::get('status');

        \admin_view('payment_methods/index', [
            'title' => 'Métodos de pago',
            'active' => 'payment_methods',
            'methods' => $this->service->all(
                $search !== '' ? $search : null,
                $type !== '' ? $type : null,
                $status !== '' ? $status : null
            ),
            'search' => $search,
            'type' => $type,
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
        $qr = Input::file('qr_image');

        try {
            $result = $this->service->create($input, $qr, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('create', null, $result['data'], $result['errors']);
                return;
            }

            \admin_flash('success', 'Método de pago creado correctamente.');
            \admin_redirect('/admin/payment-methods');
        } catch (DomainException $exception) {
            $this->renderForm('create', null, $input + $this->defaults(), ['general' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            error_log('Payment method create: ' . $exception->getMessage());
            $this->renderForm('create', null, $input + $this->defaults(), ['general' => 'No se pudo crear el método de pago.']);
        }
    }

    public function edit(int $id): void
    {
        $method = $this->service->find($id);

        if ($method === null) {
            \admin_abort(404, 'El método de pago solicitado no existe.');
        }

        $this->renderForm('edit', $method, $this->defaults($method), [], $id);
    }

    public function update(int $id): void
    {
        \admin_verify_csrf();

        $method = $this->service->find($id);

        if ($method === null) {
            \admin_abort(404, 'El método de pago solicitado no existe.');
        }

        $input = $this->input();
        $qr = Input::file('qr_image');

        try {
            $result = $this->service->update($id, $input, $qr, (int) \admin_current_user()['id']);

            if (!$result['ok']) {
                $this->renderForm('edit', $method, $result['data'], $result['errors'], $id);
                return;
            }

            \admin_flash('success', 'Método de pago actualizado correctamente.');
            \admin_redirect('/admin/payment-methods');
        } catch (DomainException $exception) {
            $this->renderForm('edit', $method, $input + $this->defaults($method), ['general' => $exception->getMessage()], $id);
        } catch (\Throwable $exception) {
            error_log('Payment method update: ' . $exception->getMessage());
            $this->renderForm('edit', $method, $input + $this->defaults($method), ['general' => 'No se pudo actualizar el método de pago.'], $id);
        }
    }

    public function toggle(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->toggle($id, Input::post('active') === '1', (int) \admin_current_user()['id']);
            \admin_flash('success', 'Estado del método de pago actualizado.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Payment method toggle: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo cambiar el estado del método de pago.');
        }

        \admin_redirect('/admin/payment-methods');
    }

    public function delete(int $id): void
    {
        \admin_verify_csrf();

        try {
            $this->service->delete($id, (int) \admin_current_user()['id']);
            \admin_flash('success', 'Método de pago eliminado correctamente.');
        } catch (DomainException $exception) {
            \admin_flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Payment method delete: ' . $exception->getMessage());
            \admin_flash('error', 'No se pudo eliminar el método de pago.');
        }

        \admin_redirect('/admin/payment-methods');
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'name' => Input::post('name'),
            'type' => Input::post('type', 'wallet'),
            'provider' => Input::post('provider', 'yape'),
            'holder_name' => Input::post('holder_name'),
            'holder_document' => Input::post('holder_document'),
            'phone' => Input::post('phone'),
            'bank_name' => Input::post('bank_name'),
            'account_number' => Input::post('account_number'),
            'cci' => Input::post('cci'),
            'contact_phone' => Input::post('contact_phone'),
            'instructions' => Input::post('instructions'),
            'is_active' => Input::bool('is_active'),
        ];
    }

    /**
     * @param array<string, mixed>|null $method
     * @return array<string, mixed>
     */
    private function defaults(?array $method = null): array
    {
        if ($method === null) {
            return [
                'name' => '',
                'type' => 'wallet',
                'provider' => 'yape',
                'holder_name' => '',
                'holder_document' => '',
                'phone' => '',
                'bank_name' => '',
                'account_number' => '',
                'cci' => '',
                'contact_phone' => '',
                'instructions' => '',
                'is_active' => true,
            ];
        }

        return [
            'name' => (string) $method['name'],
            'type' => (string) $method['type'],
            'provider' => (string) $method['provider'],
            'holder_name' => (string) $method['holder_name'],
            'holder_document' => (string) ($method['holder_document'] ?? ''),
            'phone' => (string) ($method['phone'] ?? ''),
            'bank_name' => (string) ($method['bank_name'] ?? ''),
            'account_number' => (string) ($method['account_number'] ?? ''),
            'cci' => (string) ($method['cci'] ?? ''),
            'contact_phone' => (string) ($method['contact_phone'] ?? ''),
            'instructions' => (string) ($method['instructions'] ?? ''),
            'is_active' => (bool) $method['is_active'],
        ];
    }

    /**
     * @param array<string, mixed>|null $method
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     */
    private function renderForm(string $mode, ?array $method, array $old, array $errors, int $id = 0): void
    {
        \admin_view('payment_methods/form', [
            'title' => $mode === 'edit' ? 'Editar método de pago' : 'Nuevo método de pago',
            'active' => 'payment_methods',
            'mode' => $mode,
            'method' => $method,
            'old' => $old,
            'errors' => $errors,
            'action' => $mode === 'edit' ? '/admin/payment-methods/' . $id : '/admin/payment-methods',
        ]);
    }
}
