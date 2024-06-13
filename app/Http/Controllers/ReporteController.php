<?php

namespace App\Http\Controllers;

use App\Models\unidad;
use App\Models\directivo;
use App\Models\operador;
use App\Models\calificacion;
use App\Models\estado;
use App\Models\formacionunidades;
use App\Models\ruta;
use App\Models\tipodirectivo;
use App\Models\tipooperador;
use App\Models\castigo;
use App\Models\corte;
use App\Models\entrada;
use App\Models\rolServicio;
use App\Models\ultimaCorrida;
use App\Models\tipoUltimaCorrida;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function obtenerEntradasUnidadPorSemana($idUnidad, $semana)
    {
        try {
            $inicioSemana = Carbon::now()->startOfYear()->addWeeks($semana - 1)->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $entradas = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioSemana, $finSemana])
                ->get();

            return response()->json($entradas);
        } catch (\Exception $e) {
            Log::error('Error al obtener entradas por semana', ['details' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener entradas por semana', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerEntradasUnidadPorMes($idUnidad, $mes)
    {
        try {
            // Crear las fechas de inicio y fin del mes seleccionado
            $inicioMes = Carbon::create(null, $mes)->startOfMonth();
            $finMes = Carbon::create(null, $mes)->endOfMonth();
    
            // Obtener las entradas filtradas por idUnidad y por el rango de fechas en created_at
            $entradas = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->get();
    
            // Devolver las entradas filtradas como respuesta JSON
            return response()->json($entradas);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener entradas por mes'], 500);
        }
    }

    public function obtenerEntradasUnidadPorAnio($idUnidad, $anio)
    {
        try {
            // Crear las fechas de inicio y fin del año seleccionado
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfYear();
            $finAnio = Carbon::create($anio, 12, 31)->endOfYear();

            // Obtener las entradas filtradas por idUnidad y por el rango de fechas en created_at
            $entradas = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioAnio, $finAnio])
                ->get();

            // Devolver las entradas filtradas como respuesta JSON
            return response()->json($entradas);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener entradas por año', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerEntradasTardesPorSemana($idUnidad, $semana)
    {
        try {
            $inicioSemana = Carbon::now()->startOfYear()->addWeeks($semana - 1)->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $entradasTardes = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->where('horaEntrada', '>=', '07:01:00') // Hora mayor o igual a 7:01 AM
                ->where('created_at', '>', $inicioSemana)
                ->where('created_at', '<', $finSemana)
                ->get();

            return response()->json($entradasTardes);
        } catch (\Exception $e) {
            Log::error('Error al obtener entradas tardías por semana', ['details' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener entradas tardías por semana', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerEntradasTardesPorMes($idUnidad, $mes)
    {
        try {
            $inicioMes = Carbon::create(null, $mes)->startOfMonth();
            $finMes = Carbon::create(null, $mes)->endOfMonth();

            $entradasTardes = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->where('horaEntrada', '>=', '07:01:00') // Hora mayor o igual a 7:01 AM
                ->where('created_at', '>=', $inicioMes)
                ->where('created_at', '<=', $finMes)
                ->get();

            return response()->json($entradasTardes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener entradas tardías por mes'], 500);
        }
    }

    public function obtenerEntradasTardesPorAnio($idUnidad, $anio)
    {
        try {
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfYear();
            $finAnio = Carbon::create($anio, 12, 31)->endOfYear();

            $entradasTardes = Entrada::with(['unidad.ruta', 'unidad.directivo', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->where('horaEntrada', '>=', '07:01:00') // Hora mayor o igual a 7:01 AM
                ->where('created_at', '>', $inicioAnio)
                ->where('created_at', '<', $finAnio)
                ->get();

            return response()->json($entradasTardes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener entradas tardías por año', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesPorSemana($idUnidad, $semana)
    {
        try {
            $inicioSemana = Carbon::now()->startOfYear()->addWeeks($semana - 1)->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $cortes = Corte::with(['unidad.ruta','unidad.directivo','operador','unidad.entradas'])
            ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                return $query->where('idUnidad', $idUnidad);
            })
            ->whereBetween('created_at', [$inicioSemana, $finSemana])
            ->get();

            return response()->json($cortes);
        } catch (\Exception $e) {
            Log::error('Error al obtener cortes por semana', ['details' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener cortes por semana', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesPorMes($idUnidad, $mes)
    {
        try {
            // Crear las fechas de inicio y fin del mes seleccionado
            $inicioMes = Carbon::create(null, $mes)->startOfMonth();
            $finMes = Carbon::create(null, $mes)->endOfMonth();
    
            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->get();
    
            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por mes'], 500);
        }
    }

    public function obtenerCortesPorAnio($idUnidad, $anio)
    {
        try {
            // Crear las fechas de inicio y fin del año seleccionado
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfYear();
            $finAnio = Carbon::create($anio, 12, 31)->endOfYear();

            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioAnio, $finAnio])
                ->get();

            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por año', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesCRPorSemana($idUnidad, $semana)
    {
        try {
            $inicioSemana = Carbon::now()->startOfYear()->addWeeks($semana - 1)->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $cortes = Corte::with(['unidad.ruta','unidad.directivo','operador','unidad.entradas'])
            ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                return $query->where('idUnidad', $idUnidad);
            })
            ->whereBetween('created_at', [$inicioSemana, $finSemana])
            ->whereNotNull('horaRegreso') 
            ->get();

            return response()->json($cortes);
        } catch (\Exception $e) {
            Log::error('Error al obtener cortes por semana', ['details' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener cortes por semana', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesCRPorMes($idUnidad, $mes)
    {
        try {
            // Crear las fechas de inicio y fin del mes seleccionado
            $inicioMes = Carbon::create(null, $mes)->startOfMonth();
            $finMes = Carbon::create(null, $mes)->endOfMonth();
    
            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->whereNotNull('horaRegreso') 
                ->get();
    
            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por mes'], 500);
        }
    }

    public function obtenerCortesCRPorAnio($idUnidad, $anio)
    {
        try {
            // Crear las fechas de inicio y fin del año seleccionado
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfYear();
            $finAnio = Carbon::create($anio, 12, 31)->endOfYear();

            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioAnio, $finAnio])
                ->whereNotNull('horaRegreso') 
                ->get();

            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por año', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesSRPorSemana($idUnidad, $semana)
    {
        try {
            $inicioSemana = Carbon::now()->startOfYear()->addWeeks($semana - 1)->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $cortes = Corte::with(['unidad.ruta','unidad.directivo','operador','unidad.entradas'])
            ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                return $query->where('idUnidad', $idUnidad);
            })
            ->whereBetween('created_at', [$inicioSemana, $finSemana])
            ->whereNull('horaRegreso') 
            ->get();

            return response()->json($cortes);
        } catch (\Exception $e) {
            Log::error('Error al obtener cortes por semana', ['details' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener cortes por semana', 'details' => $e->getMessage()], 500);
        }
    }

    public function obtenerCortesSRPorMes($idUnidad, $mes)
    {
        try {
            // Crear las fechas de inicio y fin del mes seleccionado
            $inicioMes = Carbon::create(null, $mes)->startOfMonth();
            $finMes = Carbon::create(null, $mes)->endOfMonth();
    
            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->whereNull('horaRegreso') 
                ->get();
    
            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por mes'], 500);
        }
    }

    public function obtenerCortesSRPorAnio($idUnidad, $anio)
    {
        try {
            // Crear las fechas de inicio y fin del año seleccionado
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfYear();
            $finAnio = Carbon::create($anio, 12, 31)->endOfYear();

            // Obtener los cortes filtradas por idUnidad y por el rango de fechas en created_at
            $cortes = corte::with(['unidad.ruta', 'unidad.directivo', 'unidad.entradas', 'operador'])
                ->when($idUnidad !== 'todas', function ($query) use ($idUnidad) {
                    return $query->where('idUnidad', $idUnidad);
                })
                ->whereBetween('created_at', [$inicioAnio, $finAnio])
                ->whereNull('horaRegreso') 
                ->get();

            // Devolver los cortes filtradas como respuesta JSON
            return response()->json($cortes);
        } catch (\Exception $e) {
            // Manejar excepciones y devolver un mensaje de error
            return response()->json(['error' => 'Error al obtener cortes por año', 'details' => $e->getMessage()], 500);
        }
    }
}