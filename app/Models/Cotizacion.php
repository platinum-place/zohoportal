<?php

namespace App\Models;

class Cotizacion
{
    public $plan;
    public $suma;
    public $planes = array();
    public $cuota;
    public $plazo;
    public $fecha_codeudor;
    public $fecha_deudor;
    public $marcaid;
    public $uso;
    public $ano;
    public $modeloid;
    public $modelotipo;
    public $estado;
    public $prestamo;
    public $construccion;
    public $riesgo;
    public $direccion;

    public function auto($marca, $modeloid, $modelotipo, $plan, $ano, $uso, $estado, $suma, $planes)
    {
        $this->marca = $marca;
        $this->modeloid = $modeloid;
        $this->modelotipo = $modelotipo;
        $this->plan = $plan;
        $this->ano = $ano;
        $this->uso = $uso;
        $this->estado = $estado;
        $this->suma = $suma;
        $this->planes = $planes;
    }

    public function incendio($suma, $prestamo, $plazo, $riesgo, $construccion, $direccion, $plan, $planes)
    {
        $this->suma = $suma;
        $this->prestamo = $prestamo;
        $this->plazo = $plazo;
        $this->riesgo = $riesgo;
        $this->construccion = $construccion;
        $this->direccion = $direccion;
        $this->plan = $plan;
        $this->planes = $planes;
    }

    public function desempleo($fecha_deudor, $cuota, $plazo, $suma, $plan, $planes)
    {
        $this->fecha_deudor = $fecha_deudor;
        $this->cuota = $cuota;
        $this->plazo = $plazo;
        $this->suma = $suma;
        $this->plan = $plan;
        $this->planes = $planes;
    }

    public function vida($fecha_deudor, $codeudor, $plazo, $suma, $plan, $planes)
    {
        $this->fecha_deudor = $fecha_deudor;
        $this->codeudor = $codeudor;
        $this->plazo = $plazo;
        $this->suma = $suma;
        $this->plan = $plan;
        $this->planes = $planes;
    }
}
