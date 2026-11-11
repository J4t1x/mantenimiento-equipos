<?php

namespace Tests\Feature\Panel;

use App\Enums\EventoAutenticacion as TipoEvento;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Resources\EventosAutenticacion\EventoAutenticacionResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\EventoAutenticacion;
use App\Models\User;
use App\Notifications\RecuperarContrasena;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * SPEC-ME-019: bloqueo por intentos fallidos (RF-87), recuperación de contraseña por correo (RF-88)
 * y registro de eventos de autenticación (RF-89). Suite de seguridad ASVS 5.0 V6 (TEST §11).
 */
class SeguridadInicioSesionTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRASENA = 'contrasena-correcta-2026';

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function usuario(array $atributos = []): User
    {
        return User::factory()->create(['email' => 'tecnico@saludaysen.cl', 'password' => self::CONTRASENA, ...$atributos]);
    }

    /**
     * Intenta ingresar. Limpia el límite de ráfagas de Filament (5 por minuto por IP), que es una
     * capa aparte de la que se prueba aquí.
     */
    private function intentar(string $email, string $contrasena)
    {
        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));

        return Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $contrasena])
            ->call('authenticate');
    }

    private function eventos(TipoEvento $evento): int
    {
        return EventoAutenticacion::query()->where('evento', $evento)->count();
    }

    // --- RF-87: bloqueo por intentos fallidos -----------------------------------------------

    public function test_la_cuenta_se_bloquea_tras_5_fallos_incluso_con_la_contrasena_correcta(): void
    {
        $this->usuario();

        for ($i = 1; $i <= 4; $i++) {
            $this->intentar('tecnico@saludaysen.cl', 'incorrecta')
                ->assertHasErrors(['data.email' => __('filament-panels::auth/pages/login.messages.failed')]);
        }

        $this->intentar('tecnico@saludaysen.cl', 'incorrecta')->assertSee('quedó bloqueado temporalmente');
        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA)->assertSee('Intente nuevamente en 15 minutos');

        $this->assertGuest();
        $this->assertSame(1, $this->eventos(TipoEvento::Bloqueo));
    }

    public function test_pasado_el_tiempo_de_bloqueo_la_cuenta_vuelve_a_ingresar(): void
    {
        $this->usuario();

        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('tecnico@saludaysen.cl', 'incorrecta');
        }

        $this->travel(16)->minutes();

        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA)->assertHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_el_bloqueo_por_cuenta_no_revela_si_el_correo_existe(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('no-existe@saludaysen.cl', 'incorrecta');
        }

        $this->intentar('no-existe@saludaysen.cl', 'otra')->assertSee('quedó bloqueado temporalmente');
    }

    public function test_un_ingreso_correcto_reinicia_los_fallos_de_la_cuenta(): void
    {
        $this->usuario();

        for ($i = 1; $i <= 4; $i++) {
            $this->intentar('tecnico@saludaysen.cl', 'incorrecta');
        }

        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA)->assertHasNoErrors();
        Auth::logout();

        $this->intentar('tecnico@saludaysen.cl', 'incorrecta')
            ->assertHasErrors(['data.email' => __('filament-panels::auth/pages/login.messages.failed')]);
    }

    public function test_la_ip_se_bloquea_tras_20_fallos_con_cuentas_distintas(): void
    {
        $this->usuario();

        for ($i = 1; $i <= 20; $i++) {
            $this->intentar("cuenta{$i}@saludaysen.cl", 'incorrecta');
        }

        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA)->assertSee('quedó bloqueado temporalmente');
        $this->assertGuest();
        $this->assertSame(1, EventoAutenticacion::query()->where('evento', TipoEvento::Bloqueo)->where('detalle->tipo', 'ip')->count());
    }

    public function test_la_recuperacion_de_contrasena_desbloquea_la_cuenta(): void
    {
        $usuario = $this->usuario();

        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('tecnico@saludaysen.cl', 'incorrecta');
        }

        $token = app('auth.password.broker')->createToken($usuario);

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => $token])
            ->fillForm(['password' => 'nueva-contrasena-segura', 'passwordConfirmation' => 'nueva-contrasena-segura'])
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->intentar('tecnico@saludaysen.cl', 'nueva-contrasena-segura')->assertHasNoErrors();
        $this->assertAuthenticated();
    }

    // --- RF-88: recuperación de contraseña por correo ---------------------------------------

    public function test_el_login_ofrece_recuperar_la_contrasena(): void
    {
        $this->get(Filament::getLoginUrl())->assertOk()->assertSee(Filament::getRequestPasswordResetUrl(), false);
    }

    public function test_la_solicitud_responde_igual_exista_o_no_el_correo_y_solo_envia_a_cuentas_activas(): void
    {
        Notification::fake();
        $this->withoutDefer();

        $activo = $this->usuario();
        $inactivo = User::factory()->create(['email' => 'inactivo@saludaysen.cl', 'activo' => false]);

        foreach (['tecnico@saludaysen.cl', 'no-existe@saludaysen.cl', 'inactivo@saludaysen.cl', 'tecnico@saludaysen.cl'] as $email) {
            RateLimiter::clear('livewire-rate-limiter:'.sha1(RequestPasswordReset::class.'|request|127.0.0.1'));

            Livewire::test(RequestPasswordReset::class)
                ->fillForm(['email' => $email])
                ->call('request')
                ->assertHasNoErrors()
                ->assertNotified('Revise su correo');
        }

        // La cuarta solicitud (repetida antes de un minuto) no envía otro correo, pero responde igual.
        Notification::assertSentToTimes($activo, RecuperarContrasena::class, 1);
        Notification::assertNotSentTo($inactivo, RecuperarContrasena::class);
        $this->assertSame(4, $this->eventos(TipoEvento::SolicitudRecuperacion));
    }

    public function test_el_correo_de_recuperacion_esta_en_espanol_e_indica_el_vencimiento(): void
    {
        $usuario = $this->usuario();
        $notificacion = new RecuperarContrasena('token');
        $notificacion->url = 'https://ejemplo/restablecer';
        $correo = $notificacion->toMail($usuario);

        $this->assertStringContainsString('Recuperación de contraseña', $correo->subject);
        $this->assertSame('https://ejemplo/restablecer', $correo->actionUrl);
        $this->assertNotInstanceOf(ShouldQueue::class, $notificacion, 'Sin worker de colas, un correo encolado no se envía.');
        $this->assertContains('El enlace vence en 60 minutos y sirve una sola vez.', [...$correo->introLines, ...$correo->outroLines]);
    }

    public function test_el_enlace_sirve_una_sola_vez(): void
    {
        $usuario = $this->usuario();
        $token = app('auth.password.broker')->createToken($usuario);

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => $token])
            ->fillForm(['password' => 'nueva-contrasena-segura', 'passwordConfirmation' => 'nueva-contrasena-segura'])
            ->call('resetPassword')
            ->assertNotified(__('passwords.reset'));

        $this->assertTrue(Hash::check('nueva-contrasena-segura', $usuario->fresh()->password));

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => $token])
            ->fillForm(['password' => 'otra-contrasena-segura', 'passwordConfirmation' => 'otra-contrasena-segura'])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));

        $this->assertTrue(Hash::check('nueva-contrasena-segura', $usuario->fresh()->password));
        $this->assertSame(1, $this->eventos(TipoEvento::RecuperacionContrasena));
    }

    public function test_el_enlace_vencido_se_rechaza(): void
    {
        $usuario = $this->usuario();
        $token = app('auth.password.broker')->createToken($usuario);

        $this->travel(61)->minutes();

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => $token])
            ->fillForm(['password' => 'nueva-contrasena-segura', 'passwordConfirmation' => 'nueva-contrasena-segura'])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));

        $this->assertTrue(Hash::check(self::CONTRASENA, $usuario->fresh()->password));
    }

    public function test_la_contrasena_nueva_exige_12_caracteres(): void
    {
        $usuario = $this->usuario();
        $token = app('auth.password.broker')->createToken($usuario);

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => $token])
            ->fillForm(['password' => 'corta123', 'passwordConfirmation' => 'corta123'])
            ->call('resetPassword')
            ->assertHasFormErrors(['password']);
    }

    // --- RF-89: registro de eventos de autenticación ----------------------------------------

    public function test_se_registran_ingresos_fallos_y_cierres_con_su_ip(): void
    {
        $usuario = $this->usuario();

        $this->intentar('tecnico@saludaysen.cl', 'incorrecta');
        $this->intentar('no-existe@saludaysen.cl', 'incorrecta');
        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA);
        Auth::logout();

        $fallos = EventoAutenticacion::query()->where('evento', TipoEvento::IngresoFallido)->orderBy('id')->get();
        $this->assertSame(['contrasena_incorrecta', 'cuenta_inexistente'], $fallos->pluck('detalle.motivo')->all());
        $this->assertSame($usuario->id, $fallos->first()->user_id);
        $this->assertNull($fallos->last()->user_id);
        $this->assertSame('no-existe@saludaysen.cl', $fallos->last()->email);

        $ingreso = EventoAutenticacion::query()->where('evento', TipoEvento::IngresoExitoso)->sole();
        $this->assertSame($usuario->id, $ingreso->user_id);
        $this->assertSame('127.0.0.1', $ingreso->ip_address);
        $this->assertSame(1, $this->eventos(TipoEvento::CierreSesion));
    }

    public function test_una_cuenta_inactiva_no_ingresa_y_queda_registrada(): void
    {
        $this->usuario(['activo' => false]);

        $this->intentar('tecnico@saludaysen.cl', self::CONTRASENA)
            ->assertHasErrors(['data.email' => __('filament-panels::auth/pages/login.messages.failed')]);

        $this->assertGuest();
        $this->assertSame('cuenta_inactiva', EventoAutenticacion::query()->sole()->detalle['motivo']);
    }

    public function test_el_cambio_de_contrasena_desde_usuarios_queda_registrado_con_quien_lo_hizo(): void
    {
        $administrador = $this->actuarComo('super_admin');
        $usuario = $this->usuario();

        Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
            ->fillForm(['password' => 'nueva-contrasena-segura'])
            ->call('save')
            ->assertHasNoFormErrors();

        $evento = EventoAutenticacion::query()->where('evento', TipoEvento::CambioContrasena)->sole();
        $this->assertSame($usuario->id, $evento->user_id);
        $this->assertSame($administrador->id, $evento->detalle['realizado_por_id']);
    }

    public function test_el_formulario_de_usuarios_exige_12_caracteres(): void
    {
        $this->actuarComo('super_admin');
        $usuario = $this->usuario();

        Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
            ->fillForm(['password' => 'corta123'])
            ->call('save')
            ->assertHasFormErrors(['password']);

        $this->assertSame(0, $this->eventos(TipoEvento::CambioContrasena));
    }

    public function test_el_registro_no_se_puede_modificar_ni_eliminar(): void
    {
        $evento = EventoAutenticacion::registrar(TipoEvento::IngresoFallido, email: 'x@saludaysen.cl');

        try {
            $evento->update(['email' => 'otro@saludaysen.cl']);
            $this->fail('Se pudo modificar el registro.');
        } catch (LogicException) {
        }

        try {
            $evento->delete();
            $this->fail('Se pudo eliminar el registro.');
        } catch (LogicException) {
        }

        $this->assertSame('x@saludaysen.cl', $evento->fresh()->email);
    }

    public function test_solo_el_administrador_consulta_el_registro_de_accesos(): void
    {
        EventoAutenticacion::registrar(TipoEvento::IngresoFallido, email: 'x@saludaysen.cl');

        foreach (['Encargado de Mantención', 'Técnico Interno', 'Encargado de Convenios', 'Jefatura'] as $rol) {
            $this->actuarComo($rol);
            $this->get(EventoAutenticacionResource::getUrl())->assertForbidden();
        }

        $this->actuarComo('super_admin');
        $this->get(EventoAutenticacionResource::getUrl())
            ->assertOk()
            ->assertSee('x@saludaysen.cl')
            ->assertSee('Ingreso fallido')
            ->assertSee('Sin cuenta');
    }
}
