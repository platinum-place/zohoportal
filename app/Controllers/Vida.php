<?php

namespace App\Controllers;

use App\Libraries\Cotizaciones;
use App\Libraries\Vida as LibrariesVida;
use App\Models\Cotizacion;

class Vida extends BaseController
{
    //funcion post
    public function cotizar()
    {
        //libreria para cotizar
        $libreria = new LibrariesVida;

        //modelo para cotizacion
        $cotizacion = new Cotizacion;

        //instanciar el modelo de cotizacion y solo usar los valores relacionados
        $cotizacion->plan = "Vida";
        $cotizacion->fecha_deudor = $this->request->getPost("deudor");
        $cotizacion->fecha_codeudor = $this->request->getPost("codeudor");
        $cotizacion->plazo = $this->request->getPost("plazo");
        $cotizacion->suma = $this->request->getPost("suma");
        $cotizacion->tipo = "vida";

        //planes relacionados al banco
        $criterio = "((Corredor:equals:" . session("usuario")->getFieldValue("Account_Name")->getEntityId() . ") and (Product_Category:equals:Vida))";
        $planes =  $libreria->searchRecordsByCriteria("Products", $criterio);

        foreach ($planes as $plan) {
            //inicializacion de variables
            $comentario = "";
            $prima = 0;

            //verificaciones
            $comentario = $libreria->verificar_limites($cotizacion, $plan);

            //si no hubo un excepcion
            if (empty($comentario)) {
                //calcular prima
                $prima = $libreria->calcular_prima($cotizacion, $plan);

                //en caso de haber algun problema
                if (is_string($prima)) {
                    $comentario = $prima;
                    $prima = 0;
                }
            }

            //lista con los resultados de cada calculo
            $cotizacion->planes[] = [
                "aseguradora" => $plan->getFieldValue('Vendor_Name')->getLookupLabel(),
                "aseguradoraid" => $plan->getFieldValue('Vendor_Name')->getEntityId(),
                "planid" => $plan->getEntityId(),
                "prima" => round($prima - ($prima * 0.16)),
                "neta" => round($prima * 0.16),
                "total" => round($prima),
                "suma" =>  $cotizacion->suma,
                "comentario" => $comentario
            ];
        }

        //alerta
        session()->setFlashdata('alerta', '¡Cotización creada exitosamente! Para descargar la cotización, haz clic en "Continuar" y completa el formulario.');

        //libreria del api para obtener todo los registros de un modulo, en este caso del de marcas
        $marcas = $libreria->getRecords("Marcas");

        //formatear el resultado para ordenarlo alfabeticamente en forma descendente
        asort($marcas);

        //vista principal
        return view("cotizaciones/index", ["titulo" => "Cotizar", "marcas" => $marcas, "cotizacion" => $cotizacion]);
    }

    public function completar()
    {
        //pasa la tabla de cotizacion en array para agregarla al registro
        $planes = json_decode($this->request->getPost("planes"), true);
        //datos generales para crear una cotizacion
        $fecha_limite = date("Y-m-d", strtotime(date("Y-m-d") . "+ 10 days"));
        $registro = [
            "Subject" => "Cotización",
            "Valid_Till" => $fecha_limite,
            "Account_Name" =>  session('usuario')->getFieldValue("Account_Name")->getEntityId(),
            "Contact_Name" =>  session('usuario')->getEntityId(),
            "Nombre" => $this->request->getPost("nombre"),
            "Apellido" => $this->request->getPost("apellido"),
            "Fecha_de_nacimiento" => $this->request->getPost("fecha"),
            "RNC_C_dula" => $this->request->getPost("rnc_cedula"),
            "Correo_electr_nico" => $this->request->getPost("correo"),
            "Direcci_n" => $this->request->getPost("direccion"),
            "Tel_Celular" => $this->request->getPost("telefono"),
            "Tel_Residencia" => $this->request->getPost("tel_residencia"),
            "Tel_Trabajo" => $this->request->getPost("tel_trabajo"),
            "Plan" => $this->request->getPost("plan"),
            "Tipo" =>  $this->request->getPost("tipo"),
            "Suma_asegurada" => $this->request->getPost("suma"),
            "Plazo" => $this->request->getPost("plazo")
        ];
        //en caso de haber un codeudor
        if ($this->request->getPost("nombre_codeudor")) {
            $codeudor = [
                "Nombre_codeudor" => $this->request->getPost("nombre_codeudor"),
                "Apellido_codeudor" => $this->request->getPost("apellido_codeudor"),
                "Tel_Celular_codeudor" => $this->request->getPost("telefono_codeudor"),
                "Tel_Residencia_codeudor" => $this->request->getPost("tel_residencia_codeudor"),
                "Tel_Trabajo_codeudor" => $this->request->getPost("tel_trabajo_codeudor"),
                "RNC_C_dula_codeudor" => $this->request->getPost("rnc_cedula_codeudor"),
                "Fecha_de_nacimiento_codeudor" => $this->request->getPost("fecha_codeudor"),
                "Direcci_n_codeudor" => $this->request->getPost("direccion_codeudor"),
                "Correo_electr_nico_codeudor" => $this->request->getPost("correo_codeudor")
            ];
            //actualiza el array general
            $registro = array_merge($registro, $codeudor);
        }

        //libreria para cotizaciones
        $libreria = new Cotizaciones;

        //crea la cotizacion el en crm
        $id = $libreria->crear_cotizacion($registro, $planes);

        //alerta general cuando se realiza una cotizacion en el crm
        session()->setFlashdata('alerta', "¡Cotización completada exitosamente! A continuación, pues descargar, emitir o editar la cotización. Para emitir, descarga la cotización y los documentos asociados a la aseguradora elegida. Luego, adjunta todos los documentos necesarios al formulario. Por último, haz clic en “Emitir”. De no hacerlo, es posible retomar la cotización en otro momento. La cotización estara activa hasta " . $fecha_limite);

        //vista para emitir
        return redirect()->to(site_url("emisiones/emitir/$id"));
    }
}
