// tests/Unit/ReservaServiceTest.php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReservaService;
use App\Models\Evento;
use App\Models\Asiento;
use App\Models\User;

class ReservaServiceTest extends TestCase
{
    public function test_puede_reservar_asiento_disponible()
    {
        // Arrange
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();
        $user = User::factory()->create();
        
        $service = new ReservaService();
        
        // Act
        $reserva = $service->reservarAsiento(
            $evento->id,
            $asiento->id,
            $user->id
        );
        
        // Assert
        $this->assertNotNull($reserva);
        $this->assertEquals('bloqueado', $reserva->estado);
        $this->assertEquals($evento->id, $reserva->evento_id);
    }
    
    public function test_no_puede_reservar_asiento_ocupado()
    {
        // Arrange
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();
        $user = User::factory()->create();
        
        // Crear reserva existente
        EstadoAsiento::create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'user_id' => $user->id,
            'estado' => 'bloqueado',
            'reservado_hasta' => now()->addMinutes(15),
        ]);
        
        $service = new ReservaService();
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no está disponible');
        
        $service->reservarAsiento($evento->id, $asiento->id, $user->id);
    }
}