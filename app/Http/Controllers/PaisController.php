<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class PaisController extends Controller
{
    public function getPaises()
    {
        try {
            return DB::table('pais')->where('Nu_Estado', 1)
            ->select('Id_Pais as value', 'No_Pais as label')
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los paises: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getDepartamentos(Request $request)
    {
        try {
            return DB::table('departamento')
                ->where('Nu_Estado', 1)
                ->select('Id_Departamento as value', 'No_Departamento as label')
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los departamentos: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getProvincias(Request $request)
    {
        try {
            Log::info('ID_Departamento: ' . $request->input('departamentoId', 0));
            return DB::table('provincia')
                ->where('Nu_Estado', 1)
                ->where("ID_Departamento", $request->input('departamentoId', 0))
                ->select('Id_Provincia as value', 'No_Provincia as label')
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las provincias: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getDistritos(Request $request)
    {
        try {
            return DB::table('distrito')
                ->where('Nu_Estado', 1)
                ->where("ID_Provincia", $request->input('provinciaId', 0))
                ->select('Id_Distrito as value', 'No_Distrito as label')
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los distritos: ' . $e->getMessage()
            ], 500);
        }
    }
}
