<?php

namespace App\Libraries;

use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Cotizaciones extends Zoho
{
    public function lista_cotizaciones()
    {
        if (session('puesto') == "Administrador") {
            $criterio = "Account_Name:equals:" . session('cuenta_id');
        } else {
            $criterio = "((Account_Name:equals:" . session('cuenta_id') . ") and (Contact_Name:equals:" . session('usuario_id') . "))";
        }

        return $this->searchRecordsByCriteria("Quotes", $criterio);
    }

    public function resumen()
    {
        $lista = array();
        $polizas = 0;
        $vencidas = 0;

        $emisiones = $this->lista_cotizaciones();
        foreach ((array)$emisiones as $emision) {
            if ($emision->getFieldValue('Quote_Stage') == "Emitida") {
                //filtrar por  mes y año actual
                if (date("Y-m", strtotime($emision->getCreatedTime())) == date("Y-m")) {
                    $lista[] =  $emision->getFieldValue('Coberturas')->getLookupLabel();
                    $polizas++;
                }

                //contador para las emisiones que vencen en el mes y año actual
                if (date("Y-m", strtotime($emision->getFieldValue('Valid_Till'))) == date("Y-m")) {
                    $vencidas++;
                }
            }
        }

        return ["lista" => array_count_values($lista), "polizas" => $polizas, "vencidas" => $vencidas, "emisiones" => $emisiones];
    }

    //crea el registro en el crm, al ser un registro con una tabla de productos es necesario...
    //funciones del sdk relacionadas al inventario y impuestos
    public function crear_cotizacion($cotizacion, array $planes)
    {
        //inicializar el api
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Quotes");

        //inicializar el registro en blanco
        $records = array();
        $record = ZCRMRecord::getInstance("Quotes", null);

        //recorre los datos para crear un registro con los nombres de los campos a los valores que correspondan
        foreach ($cotizacion as $campo => $valor) {
            $record->setFieldValue($campo, $valor);
        }

        //recorre los planes/productos al registro
        foreach ($planes as $plan) {
            $lineItem = ZCRMInventoryLineItem::getInstance(null);
            $lineItem->setListPrice($plan["total"]);
            $lineItem->setProduct(ZCRMRecord::getInstance("Products", $plan["planid"]));
            $lineItem->setQuantity(1);
            $record->addLineItem($lineItem);
        }

        array_push($records, $record);
        $responseIn = $moduleIns->createRecords($records);

        foreach ($responseIn->getEntityResponses() as $responseIns) {
            //echo "HTTP Status Code:" . $responseIn->getHttpStatusCode();
            //echo "Status:" . $responseIns->getStatus();
            //echo "Message:" . $responseIns->getMessage();
            //echo "Code:" . $responseIns->getCode();
            //echo "Details:" . json_encode($responseIns->getDetails());
            $details = json_decode(json_encode($responseIns->getDetails()), true);
        }

        return $details["id"];
    }

    public function adjuntar_documentos($documentos, $id)
    {
        foreach ($documentos as $documento) {
            if ($documento->isValid() && !$documento->hasMoved()) {
                //subir el archivo al servidor
                $documento->move(WRITEPATH . 'uploads');

                //ruta del archivo subido
                $ruta = WRITEPATH . 'uploads/' . $documento->getClientName();

                //funcion para adjuntar el archivo
                $this->uploadAttachment("Quotes", $id, $ruta);

                //borrar el archivo del servidor local
                unlink($ruta);
            }
        }
    }

    public function generar_reporte($desde, $hasta)
    {
        //iniciar las librerias de la api para generar excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add a drawing to the worksheet
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath(FCPATH . 'img/nobe.png');
        $drawing->setCoordinates('A1');
        $drawing->setHeight(200);
        $drawing->setWorksheet($spreadsheet->getActiveSheet());

        //celdas en negrita
        $sheet->getStyle('D1')->getFont()->setBold(true)->setName('Arial')->setSize(14);
        $sheet->getStyle('D2')->getFont()->setBold(true)->setName('Arial')->setSize(12);
        $sheet->getStyle('D4')->getFont()->setBold(true);
        $sheet->getStyle('D5')->getFont()->setBold(true);
        $sheet->getStyle('D6')->getFont()->setBold(true);
        $sheet->getStyle('D7')->getFont()->setBold(true);

        //titulos del reporte
        $sheet->setCellValue('D1', session("cuenta"));
        $sheet->setCellValue('D2', 'EMISIONES');
        $sheet->setCellValue('D4', 'Generado por:');
        $sheet->setCellValue('E4', session("usuario"));
        $sheet->setCellValue('D5', 'Desde:');
        $sheet->setCellValue('E5', $desde);
        $sheet->setCellValue('D6', 'Hasta:');
        $sheet->setCellValue('E6', $hasta);

        //titulos de las columnas de tabla
        $sheet->setCellValue('A12', 'Num');
        $sheet->setCellValue('B12', 'Referidor');
        $sheet->setCellValue('C12', 'Plan');
        $sheet->setCellValue('D12', 'Aseguradora');
        $sheet->setCellValue('E12', 'Suma asegurada');
        $sheet->setCellValue('F12', 'Prima');
        $sheet->setCellValue('G12', 'Cliente');
        $sheet->setCellValue('H12', 'RNC/Cédula');
        $sheet->setCellValue('I12', 'Tel. Residencia');
        $sheet->setCellValue('J12', 'Fecha de nacimiento');
        $sheet->setCellValue('K12', 'Dirección');
        $sheet->setCellValue('L12', 'Marca');
        $sheet->setCellValue('M12', 'Modelo');
        $sheet->setCellValue('N12', 'Año');
        $sheet->setCellValue('O12', 'Color');
        $sheet->setCellValue('P12', 'Placa');
        $sheet->setCellValue('Q12', 'Chasis');
        $sheet->setCellValue('R12', 'Tipo vehículo');
        $sheet->setCellValue('S12', 'Plazos');
        $sheet->setCellValue('T12', 'Cuota Mensual de Préstamo');
        $sheet->setCellValue('U12', 'Valor del Préstamo');
        $sheet->setCellValue('V12', 'Codeudor');

        //inicializar contadores
        $cont = 1;
        $pos = 13;

        //inicializar contadores
        $cont = 1;
        $pos = 13;

        $emisiones = $this->lista_cotizaciones();

        foreach ($emisiones as $emisiones => $emision) {
            if (
                date("Y-m-d", strtotime($emision->getCreatedTime())) >= $desde
                and
                date("Y-m-d", strtotime($emision->getCreatedTime())) <= $hasta
                and
                $emision->getFieldValue('Quote_Stage') == "Emitida"
            ) {
                //valores de la tabla
                $sheet->setCellValue('A' . $pos, $cont);
                $sheet->setCellValue('B' . $pos, $emision->getFieldValue('Contact_Name')->getLookupLabel());
                $sheet->setCellValue('C' . $pos, $emision->getFieldValue('Plan'));
                $sheet->setCellValue('D' . $pos, $emision->getFieldValue('Coberturas')->getLookupLabel());
                $sheet->setCellValue('E' . $pos, $emision->getFieldValue('Suma_asegurada'));
                $sheet->setCellValue('F' . $pos, $emision->getFieldValue('Prima'));

                //valores relacionados al cliente
                $sheet->setCellValue('G' . $pos, $emision->getFieldValue("Nombre") . " " . $emision->getFieldValue("Apellido"));
                $sheet->setCellValue('H' . $pos, $emision->getFieldValue('RNC_C_dula'));
                $sheet->setCellValue('I' . $pos, $emision->getFieldValue('Tel_Residencia'));
                $sheet->setCellValue('J' . $pos, $emision->getFieldValue('Fecha_de_nacimiento'));
                $sheet->setCellValue('K' . $pos, $emision->getFieldValue('Direcci_n'));

                //relacionados al vehiculo
                $sheet->setCellValue('L' . $pos, $emision->getFieldValue('Marca')->getLookupLabel());
                $sheet->setCellValue('M' . $pos, $emision->getFieldValue('Modelo')->getLookupLabel());
                $sheet->setCellValue('N' . $pos, $emision->getFieldValue('A_o'));
                $sheet->setCellValue('O' . $pos, $emision->getFieldValue('Color'));
                $sheet->setCellValue('P' . $pos, $emision->getFieldValue('Placa'));
                $sheet->setCellValue('Q' . $pos, $emision->getFieldValue('Chasis'));
                $sheet->setCellValue('R' . $pos, $emision->getFieldValue('Tipo'));

                //otros
                $sheet->setCellValue('S' . $pos, $emision->getFieldValue('Plazo'));
                $sheet->setCellValue('T' . $pos, $emision->getFieldValue('Placa'));
                $sheet->setCellValue('U' . $pos, $emision->getFieldValue('Cuota'));
                $sheet->setCellValue('V' . $pos, $emision->getFieldValue('Nombre_codeudor'));

                //contadores
                $cont++;
                $pos++;
            }
        }

        //cambiar el color de fondo de un rango de celdas
        $spreadsheet
            ->getActiveSheet()
            ->getStyle('A12:V12')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('004F97');

        //cambiar el color de fuente de un rango de celdas
        $spreadsheet->getActiveSheet()
            ->getStyle('A12:V12')
            ->getFont()
            ->getColor()
            ->setARGB("FFFFFF");

        //ajustar tamaño de las columnas
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setWidth(20);
        $sheet->getColumnDimension('Q')->setWidth(20);
        $sheet->getColumnDimension('R')->setWidth(20);
        $sheet->getColumnDimension('S')->setWidth(20);
        $sheet->getColumnDimension('T')->setWidth(20);
        $sheet->getColumnDimension('U')->setWidth(20);
        $sheet->getColumnDimension('V')->setWidth(20);

        //ruta del excel
        $doc = WRITEPATH . 'uploads/reporte.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($doc);

        return $doc;
    }
}
