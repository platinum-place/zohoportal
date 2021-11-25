<?php
namespace app\Libraries;

use App\Models\Cotizacion;

class Cotizar
{
    protected $zoho;
    protected $cotizacion;

    public function __construct(Zoho $zoho, Cotizacion $cotizacion)
    {
        $this->zoho = $zoho;
        $this->cotizacion = $cotizacion;
    }

    protected function limite_suma($Suma_asegurada_min, $Suma_asegurada_max): string
    {
        if ($this->cotizacion->suma < $Suma_asegurada_min and $this->cotizacion->suma > $Suma_asegurada_max) {
            return "La suma asegurada no esta dentro de los limites.";
        }

        return "";
    }

    protected function limite_plazo($Plazo_max): string
    {
        if ($this->cotizacion->plazo > $Plazo_max) {
            return "El plazo es mayor al limite establecido.";
        }

        return "";
    }

    protected function calcular_edad($fecha)
    {
        list ($ano, $mes, $dia) = explode("-", $fecha);
        $ano_diferencia = date("Y") - $ano;
        $mes_diferencia = date("m") - $mes;
        $dia_diferencia = date("d") - $dia;
        if ($dia_diferencia < 0 || $mes_diferencia < 0)
            $ano_diferencia --;
        return $ano_diferencia;
    }
}

