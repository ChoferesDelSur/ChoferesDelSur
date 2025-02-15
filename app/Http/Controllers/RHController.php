<?php

namespace App\Http\Controllers;

use App\Models\unidad;
use App\Models\directivo;
use App\Models\operador;
use App\Models\calificacion;
use App\Models\estado;
use App\Models\ruta;
use App\Models\tipoDirectivo;
use App\Models\tipoOperador;
use App\Models\castigo;
use App\Models\corte;
use App\Models\usuario;
use App\Models\incapacidad;
use App\Models\tipoUsuario;
use App\Models\convenioPago;
use App\Models\movimiento;
use App\Models\codigoPostal;
use App\Models\entrada;
use App\Models\empresa;
use App\Models\personal;
use App\Models\vacaciones;
use App\Models\escolaridad;
use App\Models\tipoMovimiento;
use App\Models\direccion;
use App\Models\rolServicio;
use App\Models\ultimaCorrida;
use App\Models\tipoUltimaCorrida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RHController extends Controller
{
    public function obtenerUsuario()
    {
        return auth()->user();
    }

    public function obtenerTipoUsuario($idTipoUsuario)
    {
        return tipoUsuario::find($idTipoUsuario);
    }

    public function obtenerInfoUsuario()
    {
        $idUsuario = auth()->user()->idUsuario;
        $usuario = usuario::find($idUsuario);
        $usuario->tipoUsuario3 = $usuario->tipoUsuario->tipoUsuario;

        return $usuario;
    }

    public function inicio()
    {
        $usuario = $this->obtenerInfoUsuario();
        if ($usuario->cambioContrasenia === 0) {
            $fechaLimite = Carbon::parse($usuario->fecha_Creacion)->addHours(48);
            $fechaFormateada = $fechaLimite->format('d/m/Y');
            $horaFormateada = $fechaLimite->format('H:i');
            $message = "Tiene hasta el " . $fechaFormateada . " a las " . $horaFormateada . " hrs para realizar el cambio de contraseña, en caso contrario, esta se desactivará y será necesario comunicarse con el administrador para solucionar la situación";
            $color = "red";
            return Inertia::render('RH/Inicio',[
                'usuario' => $usuario,
                'message' => $message /* session('message') */,
                'color' => $color,
                'type' => session('type'),
            ]);
        }
        return Inertia::render('RH/Inicio',[
            'usuario' => $usuario,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function perfil()
    {
        try {
            $usuario = $this->obtenerInfoUsuario();

            return Inertia::render('RH/Perfil', [
                'usuario' => $usuario,
                'message' => session('message'),
                'color' => session('color'),
                'type' => session('type'),
            ]);
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function actualizarContrasenia(Request $request)
    {
        try {
            $usuario = usuario::find($request->idUsuario);
            $user = Auth::user();
            if (Hash::check($request->password_actual, $user->password)) {
                $usuario->contrasenia = $request->password_nueva;
                $usuario->password = bcrypt($request->password_nueva);
                $usuario->cambioContrasenia = 1;
                $usuario->save();

                return redirect()->route('rh.perfil')->With(["message" => "Contraseña actualizada correctamente, recuerde su contraseña: " . $usuario->contrasenia, "color" => "green",'type' => 'success']);
            }
            return redirect()->route('rh.perfil')->With(["message" => "Contraseña actual incorrecta", "color" => "red",'type' => 'error']);
        } catch (Exception $e) {
            return redirect()->route('rh.perfil')->With(["message" => "Error al actualizar contraseña", "color" => "red",'type' => 'error']);
            dd($e);
        }
    }

    public function operadores(){
        $operador = operador::with(['empresa', 'tipoOperador', 'convenioPago', 'direccion', 'estado'])->get();
        //$operador = operador::with('direccion.asentamiento.municipio.estados','direccion.asentamiento.codigoPostal')->get();
        $tipoOperador = tipoOperador::all();
        $estado = estado::all();
        $directivo = directivo::all();
        $incapacidad = incapacidad::all();
        $codigoPostal = codigoPostal::all();
        $direccion = direccion::all();
        //$direccion = direccion::with('asentamiento.municipio.estados', 'asentamiento.codigoPostal')->get();
        // Aquí se ajusta el operadorDireccion a cada operador y agrega propiedades al operador
        $operador->each(function($operador) use ($direccion) {
            $domicilio = $direccion->where('idDireccion', $operador->idDireccion)->first();
            $operador->domicilio = $domicilio ? $domicilio->calle . " #" . $domicilio->numero . ", " . $domicilio->asentamiento->asentamiento . ", " . $domicilio->asentamiento->municipio->municipio . ", " . $domicilio->asentamiento->municipio->estados->entidad . ", " . $domicilio->asentamiento->codigoPostal->codigoPostal : null;
            $operador->calle = $domicilio ? $domicilio->calle : null;
            $operador->numero = $domicilio ? $domicilio->numero : null;
            $operador->codigoPostal = $domicilio ? $domicilio->asentamiento->codigoPostal->codigoPostal : null;
            $operador->idAsentamiento = $domicilio ? $domicilio->asentamiento->idAsentamiento : null;
            $operador->idMunicipio = $domicilio ? $domicilio->asentamiento->municipio->idMunicipio : null;
            $operador->idEntidad = $domicilio ? $domicilio->asentamiento->municipio->estados->idEntidad : null;
        });
        $empresa = empresa::all();
        $convenioPago = convenioPago::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/Operadores',[
            'usuario' => $usuario,
            'operador' => $operador,
            'tipoOperador' => $tipoOperador,
            'estado' => $estado,
            'incapacidad' => $incapacidad,
            'directivo' => $directivo,
            'empresa' => $empresa,
            'convenioPago' => $convenioPago,
            'codigoPostal' => $codigoPostal,
            'direccion' => $direccion,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addOperador(Request $request){
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'fechaNacimiento' => 'required',
                'edad' => 'required',
                'CURP' => 'required',
                'RFC' => 'required',
                'numTelefono' => 'required',
                'NSS' => 'required',
                'tipoOperador' => 'required',
                'estado' => 'required',
                'fechaAlta' => 'required',
                'fechaBaja' => 'nullable',
                'empresaa' => 'required',
                'convenioPa'=> 'required',
                'antiguedad' => 'required',
                'numLicencia' => 'required',
                'vigenciaLicencia' => 'required',
                'numINE' => 'required',
                'vigenciaINE' => 'required',
                'constanciaSF' => 'nullable|boolean',
                'cursoSEMOVI' => 'nullable|boolean',
                'constanciaSEMOVI' => 'nullable|boolean',
                'cursoPsicologico' => 'nullable|boolean',
                'ultimoContrato' => 'required',
                'directivo' => 'required',
            ]);
            // Verificar si el operador ya existe
            $existingOperador = operador::where('CURP', $request->CURP)->first();
            if($existingOperador){
            // Operador ya existe, puedes devolver una respuesta indicando el error
            return redirect()->route('rh.operadores')->with(['message' => "El operador ya está registrado: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, " con CURP:" . $request->CURP, "color" => "yellow", 'type' => 'info']);
            }
            // Obtener el directivo correspondiente
            $directivo = directivo::find($request->directivo);
            if (!$directivo) {
                return redirect()->route('rh.operadores')->with(['message' => "Socio/Prestador no encontrado", "color" => "red", 'type' => 'error']);
            }

            // Función para calcular el número máximo de operadores permitidos
            function maxOperadoresPermitidos($numUnidades) {
                // Calcular el múltiplo de 5 menor o igual
                $mUltimo = floor($numUnidades / 5) * 5;
                // Incremento basado en el múltiplo de 5
                $incremento = ($mUltimo / 5);
                return $numUnidades + $incremento;
            }

            // Verificar si el número de operadores excede el límite permitido
            $maxOperadores = maxOperadoresPermitidos($directivo->numUnidades);
            if ($directivo->numOperadores >= $maxOperadores) {
                return redirect()->route('rh.operadores')->with([
                    'message' => "El Socio/Prestador ha alcanzado el límite de operadores permitido para su número de unidades. Unidades: {$directivo->numUnidades}, Operadores: {$directivo->numOperadores}.",
                    "color" => "red",
                    'type' => 'error'
                ]);
            }
            //fechaFormateada
            $fechaFormateada = date('ymd', strtotime($request->fechaNacimiento));
            //Se guarda el domicilio del profesor
            $domicilio = new direccion();
            $domicilio->calle = $request->calle;
            $domicilio->numero = $request->numero;
            $domicilio->idAsentamiento = $request->asentamiento;
            $domicilio->save();
    
            $operador = new operador();
            $operador->nombre = $request->nombre;
            $operador->apellidoP = $request->apellidoP;
            $operador->apellidoM = $request->apellidoM;
            $operador->fechaNacimiento = $request->fechaNacimiento;
            $operador->edad = $request->edad;
            $operador->CURP = $request->CURP;               
            $operador->RFC = $request->RFC;
            $operador->numTelefono = $request->numTelefono;
            $operador->NSS = $request->NSS;

            $operador->idTipoOperador = $request->tipoOperador;
            $operador->idEstado = $request->estado;
            $operador->fechaAlta = $request->fechaAlta;
            $operador->fechaBaja = $request->fechaBaja;
            $operador->idEmpresa = $request->empresaa;//le puse empresaa como esta en mi formulario
            $operador->idConvenioPago = $request->convenioPa;
            $operador->antiguedad = $request->antiguedad;

            $operador->idDireccion = $domicilio->idDireccion;

            $operador->numLicencia = $request->numLicencia;
            $operador->vigenciaLicencia = $request->vigenciaLicencia;
            $operador->numINE = $request->numINE;
            $operador->vigenciaINE = $request->vigenciaINE;
            $operador->constanciaSF = $request->constanciaSF ?? false;
            $operador->cursoSemovi = $request->cursoSemovi ?? false;
            $operador->constanciaSemovi = $request->constanciaSemovi ?? false;
            $operador->cursoPsicologico = $request->cursoPsicologico ?? false;

            $operador->ultimoContrato = $request->ultimoContrato;
            $operador->idDirectivo = $request->directivo;

            $nombreCompleto = $operador->apellidoP . ' ' . $operador->apellidoM . ' ' . $operador->nombre;
            $operador->nombre_completo = $nombreCompleto;

            $operador->save();

            // Solo aumentar el número de operadores si el tipo es "Base"
            $tipoOperador = tipoOperador::find($request->tipoOperador);
            if ($tipoOperador && $tipoOperador->tipOperador === 'Base') {
                $directivo->numOperadores += 1;
                $directivo->save();
            }

            // Registrar el movimiento
            $movimiento = new movimiento();
            $movimiento->fechaMovimiento = $request->fechaAlta;
            $movimiento->idEstado = 1; // Alta
            $movimiento->idTipoMovimiento = 1; // Nuevo Ingreso
            $movimiento->idDirectivo = $request->directivo;
            $movimiento->idOperador = $operador->idOperador; // Asegúrate de que el ID del operador se establezca correctamente
            $movimiento->save();
            
            return redirect()->route('rh.operadores')->with(['message' => "Operador agregado correctamente: $nombreCompleto", "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('rh.operadores')->with(['message' => "Error al agregar al operador", "color" => "red", 'type' => 'error']);
        }
    }

        public function actualizarOperador(Request $request, $idOperador)
    {
        try {
            // Validaciones básicas
            $request->validate([
                'nombre' => 'required',
                'apellidoP' => 'required',
                'apellidoM' => 'required',
                'tipoOperador' => 'required',
                'directivo' => 'required',
                'fechaNacimiento' => 'required',
                'edad' => 'required',
                'CURP' => 'required',
                'RFC' => 'required',
                'numTelefono' => 'required',
                'NSS' => 'required',
                'fechaAlta' => 'required',
                'fechaBaja' => 'nullable',
                'empresaa' => 'required',
                'convenioPa' => 'required',
                'antiguedad' => 'required',
                'numLicencia' => 'required',
                'vigenciaLicencia' => 'required',
                'vigenciaINE' => 'required',
                'constanciaSF' => 'nullable|boolean',
                'cursoSemovi' => 'nullable|boolean',
                'constanciaSemovi' => 'nullable|boolean',
                'cursoPsicologico' => 'nullable|boolean',
                'ultimoContrato' => 'required',
            ]);

            // Encontrar el operador
            $operador = operador::find($idOperador);
            if (!$operador) {
                return redirect()->route('rh.operadores')->with(['message' => "Operador no encontrado", "color" => "red", 'type' => 'error']);
            }

            // Obtener el tipo de operador actual y el nuevo tipo
            $tipoActual = $operador->idTipoOperador;
            $nuevoTipo = $request->tipoOperador;

            // Verificar si el directivo cambia
            if ($operador->idDirectivo != $request->directivo) {
                // Decrementar el numOperadores del directivo anterior si el operador era de tipo "Base"
                $directivoAnterior = directivo::find($operador->idDirectivo);
                if ($directivoAnterior && $tipoActual == 1) { // 1 representa "Base"
                    $directivoAnterior->numOperadores = max(0, $directivoAnterior->numOperadores - 1);
                    $directivoAnterior->save();
                }

                // Incrementar el numOperadores del nuevo directivo si el operador es de tipo "Base"
                $nuevoDirectivo = directivo::find($request->directivo);
                if ($nuevoDirectivo && $nuevoTipo == 1) { // 1 representa "Base"
                    $nuevoDirectivo->numOperadores += 1;
                    $nuevoDirectivo->save();
                }
            } else {
                // Si el directivo no cambia, verificar si el tipo de operador cambia
                if ($tipoActual != $nuevoTipo) {
                    $directivo = directivo::find($operador->idDirectivo);
                    if ($directivo) {
                        if ($tipoActual == 1 && $nuevoTipo == 2) { // De "Base" a "Eventual"
                            $directivo->numOperadores = max(0, $directivo->numOperadores - 1);
                        } elseif ($tipoActual == 2 && $nuevoTipo == 1) { // De "Eventual" a "Base"
                            $directivo->numOperadores += 1;
                        }
                        $directivo->save();
                    }
                }
            }

            // Actualizar la dirección si es necesario
            if ($request->has('calle') || $request->has('numero') || $request->has('asentamiento')) {
                $domicilio = direccion::find($operador->idDireccion);
                if ($domicilio) {
                    $domicilio->calle = $request->calle ?? $domicilio->calle;
                    $domicilio->numero = $request->numero ?? $domicilio->numero;
                    $domicilio->idAsentamiento = $request->asentamiento ?? $domicilio->idAsentamiento;
                    $domicilio->save();
                }
            }

            // Determinar la fecha actual
            $fechaActual = now();
            // Lógica para actualizar fechas según el estado
            if ($request->estado == 1) { // Estado 'Alta'
                $operador->fechaAlta = $fechaActual;
                /* $operador->fechaBaja = null; */ // Limpiar fechaBaja si se cambia a Alta
            } elseif ($request->estado == 2) { // Estado 'Baja'
                $operador->fechaBaja = $fechaActual;
            }

            // Actualizar el operador
            $operador->nombre = $request->nombre;
            $operador->apellidoP = $request->apellidoP;
            $operador->apellidoM = $request->apellidoM;
            $operador->idTipoOperador = $request->tipoOperador;
            $operador->idDirectivo = $request->directivo;

            // Campos adicionales
            $operador->fechaNacimiento = $request->fechaNacimiento ?? $operador->fechaNacimiento;
            $operador->edad = $request->edad ?? $operador->edad;
            $operador->CURP = $request->CURP ?? $operador->CURP;
            $operador->RFC = $request->RFC ?? $operador->RFC;
            $operador->numTelefono = $request->numTelefono ?? $operador->numTelefono;
            $operador->NSS = $request->NSS ?? $operador->NSS;
            $operador->idEmpresa = $request->empresaa ?? $operador->idEmpresa;
            $operador->idConvenioPago = $request->convenioPa ?? $operador->idConvenioPago;
            $operador->antiguedad = $request->antiguedad ?? $operador->antiguedad;
            $operador->numLicencia = $request->numLicencia ?? $operador->numLicencia;
            $operador->vigenciaLicencia = $request->vigenciaLicencia ?? $operador->vigenciaLicencia;
            $operador->numINE = $request->numINE ?? $operador->numINE;
            $operador->vigenciaINE = $request->vigenciaINE ?? $operador->vigenciaINE;
            $operador->constanciaSF = $request->constanciaSF ?? $operador->constanciaSF;
            $operador->cursoSemovi = $request->cursoSemovi ?? $operador->cursoSemovi;
            $operador->constanciaSemovi = $request->constanciaSemovi ?? $operador->constanciaSemovi;
            $operador->cursoPsicologico = $request->cursoPsicologico ?? $operador->cursoPsicologico;
            $operador->ultimoContrato = $request->ultimoContrato ?? $operador->ultimoContrato;

            // Actualizar el nombre completo
            $operador->nombre_completo = $operador->apellidoP . ' ' . $operador->apellidoM . ' ' . $operador->nombre;

            $operador->save();

            return redirect()->route('rh.operadores')->with(['message' => "Operador actualizado correctamente: " . $request->nombre . " " . $request->apellidoP . " " . $request->apellidoM, "color" => "green", 'type' => 'success']);
        } catch (Exception $e) {
            Log::error('Error al actualizar al operador:', ['error' => $e->getMessage()]);
            return redirect()->route('rh.operadores')->with(['message' => "Error al actualizar al operador", "color" => "red", 'type' => 'error']);
        }
    }

        public function eliminarOperador($operadoresIds)
    {
        try {
            // Convierte la cadena de IDs en un array
            $operadoresIdsArray = explode(',', $operadoresIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $operadoresIdsArray = array_map('intval', $operadoresIdsArray);

            // Encuentra los operadores antes de eliminarlos
            $operadores = operador::whereIn('idOperador', $operadoresIdsArray)->get();

            foreach ($operadores as $operador) {
                // Encuentra el directivo asociado y decrementar su numOperadores
                $directivo = directivo::find($operador->idDirectivo);
                if ($directivo) {
                    $directivo->numOperadores = max(0, $directivo->numOperadores - 1);
                    $directivo->save();
                }
            }

            // Desasocia al operador de cualquier unidad asignada antes de eliminarlo
             unidad::whereIn('idOperador', $operadoresIdsArray)->update(['idOperador' => null]);

            // Elimina los operadores
            operador::whereIn('idOperador', $operadoresIdsArray)->delete();

            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.operadores')->with(['message' => "Operador eliminado correctamente", "color" => "green"]);
        } catch (Exception $e) {
            Log::error('Error al eliminar al operador:', ['error' => $e->getMessage()]);
            return redirect()->route('rh.operadores')->with(['message' => "No se pudo eliminar al operador", "color" => "red"]);
        }
    }

    public function sociosPrestadores(){
        $directivo = directivo::all();
        $operador = operador::all();
        $tipDirectivo = tipoDirectivo::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/SociosPrestadores',[
            'usuario' => $usuario,
            'directivo' => $directivo,
            'operador' => $operador,
            'tipDirectivo' => $tipDirectivo,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addDirectivo(Request $request){
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'tipDirectivo' => 'required',
            ]);
            // Verificar si ya existe un directivo con el mismo nombre completo
            $nombreCompleto = $request->apellidoP . ' ' . $request->apellidoM . ' ' . $request->nombre;
            $directivoExistente = directivo::where('nombre_completo', $nombreCompleto)->first();
            if($directivoExistente) {
                // Si ya existe un directivo con el mismo nombre completo, retornar un mensaje de error o realizar la acción correspondiente
                return redirect()->route('rh.sociosPrestadores')->with(['message' => "El directivo ya está registrado: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, "color" => "yellow", 'type' => 'info']);
            }
            $directivo = new directivo();
            $directivo->nombre = $request->nombre;
            $directivo->apellidoP = $request->apellidoP;
            $directivo->apellidoM = $request->apellidoM;
            $directivo->idTipoDirectivo = $request->tipDirectivo;
            $nombreCompleto =$directivo->apellidoP . ' ' . $directivo->apellidoM. ' ' . $directivo->nombre;
            $directivo->nombre_completo = $nombreCompleto;

            $directivo->save();
            return redirect()->route('rh.sociosPrestadores')->with(['message' => "Directivo agregado correctamente: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('rh.sociosPrestadores')->with(['message' => "Error al agregar al directivo", "color" => "red", 'type' => 'error']);
        }
    }

    public function actualizarDirectivo(Request $request, $idDirectivo)
    {
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'tipDirectivo' => 'required',
            ]);
            $directivo = directivo::find($idDirectivo);
            $directivo->nombre = $request->nombre;
            $directivo->apellidoP = $request->apellidoP;
            $directivo->apellidoM = $request->apellidoM;
            $directivo->idTipoDirectivo = $request->tipDirectivo;
            $nombreCompleto =$directivo->apellidoP . ' ' . $directivo->apellidoM. ' ' . $directivo->nombre;
            $directivo->nombre_completo = $nombreCompleto;

            $directivo->save();

            return redirect()->route('rh.sociosPrestadores')->with(['message' => "Directivo actualizado correctamente: " . $nombreCompleto, "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('rh.sociosPrestadores')->with(['message' => "El directivo no se actualizó correctamente: " . $nombreCompleto, "color" => "reed"]);
        }
    }

    public function eliminarDirectivo($directivosIds){
        try{
            // Convierte la cadena de IDs en un array
            $directivosIdsArray = explode(',', $directivosIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $directivosIdsArray = array_map('intval', $directivosIdsArray);
            // Elimina las materias
            directivo::whereIn('idDirectivo', $directivosIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.sociosPrestadores')->with(['message' => "Directivo eliminada correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('rh.sociosPrestadores')->with(['message' => "No se pudo eliminar al directivo", "color" => "red"]);
        }
    }

    public function incapacidades(){
        $incapacidad = incapacidad::all();
        $operador = operador::all();
        $operadoresAlta = operador::where('idEstado', 1)->get();
        $operadoresIncapacidad = operador::where('idEstado', 3)->get();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/Incapacidades',[
            'incapacidad' => $incapacidad,
            'usuario' => $usuario,
            'operador' => $operador,
            'operadoresAlta' => $operadoresAlta,
            'operadoresIncapacidad' => $operadoresIncapacidad,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addIncapacidad(Request $request){
        try{
            $request->validate([
                'motivo' => 'required',
                'numeroDias' => 'required',
                'fechaInicio' => 'required',
                'fechaFin' => 'required',
                'chofer' => 'required|exists:operador,idOperador', // Verifica que el operador exista
            ]);
            // Crear un nuevo registro de incapacidad
            incapacidad::create([
                'motivo' => $request->input('motivo'),
                'numeroDias' => $request->input('numeroDias'),
                'fechaInicio' => $request->input('fechaInicio'),
                'fechaFin' => $request->input('fechaFin'),
                'idOperador' => $request->input('chofer'),
            ]);
            // Recuperar el operador relacionado con la incapacidad
            $operador = operador::find($request->input('chofer'));
            if ($operador) {
                // Actualizar el estado del operador a 'incapacidad' (idEstado = 3)
                $operador->update(['idEstado' => 3]);
                $nombreCompleto = $operador->nombre_completo;

                // Disociar el operador de la unidad si está asociado
                $unidad = $operador->unidad;
                if ($unidad) {
                    $unidad->operador()->dissociate(); // Disociar el operador de la unidad
                    $unidad->save(); // Guardar cambios en la unidad
                }

            } else {
                $nombreCompleto = 'desconocido';
            }    
            // Redirigir con un mensaje de éxito que incluye el nombre completo del operador
            return redirect()->route('rh.incapacidades')->with([
                'message' => 'Incapacidad registrado correctamente del operador: ' . $nombreCompleto,
                'color' => 'green',
                'type' => 'success'
            ]);
        }catch(Exception $e){
            return redirect()->route('rh.incapacidades')->with(['message' => "Error al agregar la incapacidad", "color" => "red", 'type' => 'error']);
        }
    }

    public function actualizarIncapacidad(Request $request, $idIncapacidad)
    {
        try {
            // Validar los campos del request
            $request->validate([
                'motivo' => 'required',
                'numeroDias' => 'required',
                'fechaInicio' => 'required',
                'fechaFin' => 'required',
            ]);
            // Buscar la incapacidad por su id
            $incapacidad = incapacidad::find($idIncapacidad);
            // Verificar si la incapacidad existe
            if (!$incapacidad) {
                return redirect()->route('rh.incapacidades')->with(['message' => "Incapacidad no encontrada", "color" => "red"]);
            }
            // Actualizar los campos de la incapacidad
            $incapacidad->motivo = $request->motivo;
            $incapacidad->numeroDias = $request->numeroDias;
            $incapacidad->fechaInicio = $request->fechaInicio;
            $incapacidad->fechaFin = $request->fechaFin;
            // Guardar los cambios
            $incapacidad->save();

            // Crear el mensaje con la información actualizada
            $message = "Incapacidad actualizada correctamente. Motivo: " . $incapacidad->motivo . 
            ", Número de días: " . $incapacidad->numeroDias . 
            ", Fecha de inicio: " . $incapacidad->fechaInicio . 
            ", Fecha de fin: " . $incapacidad->fechaFin;
    
            return redirect()->route('rh.incapacidades')->with(['message' => $message, "color" => "green"]);
        } catch (Exception $e) {
            // Capturar cualquier excepción y redirigir con un mensaje de error
            return redirect()->route('rh.incapacidades')->with(['message' => "Error al actualizar la incapacidad", "color" => "red"]);
        }
    }

    public function eliminarIncapacidad($incapacidadesIds){
        try{
            // Convierte la cadena de IDs en un array
            $incapacidadesIdsArray = explode(',', $incapacidadesIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $incapacidadesIdsArray = array_map('intval', $incapacidadesIdsArray);
            // Elimina las materias
            incapacidad::whereIn('idIncapacidad', $incapacidadesIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.incapacidades')->with(['message' => "Incapacidad eliminada correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('rh.incapacidades')->with(['message' => "No se pudo eliminar la incapacidad", "color" => "red"]);
        }
    }

    public function reincorporarOperador(Request $request){
        try {
            // Validar la solicitud
            $request->validate([
                'chofer' => 'required|exists:operador,idOperador', // Verifica que el operador exista
            ]);
            // Recuperar el operador
            $operador = operador::find($request->input('chofer'));
            if ($operador) {
                // Actualizar el estado del operador a 'Alta' (idEstado = 1)
                $operador->update(['idEstado' => 1]);
                // Recuperar el nombre completo del operador
                $nombreCompleto = $operador->nombre_completo;
                // Redirigir con un mensaje de éxito que incluye el nombre completo del operador
                return redirect()->route('rh.incapacidades')->with(['message' => 'Operador ' . $nombreCompleto . ' reincorporado correctamente','color' => 'green','type' => 'success'
                ]);
            } else {
                // Si el operador no se encuentra
                return redirect()->route('rh.incapacidades')->with(['message' => 'Operador no encontrado','color' => 'red','type' => 'error'
                ]);
            }
        } catch (Exception $e) {
            // Capturar cualquier excepción y redirigir con un mensaje de error
            return redirect()->route('rh.incapacidades')->with(['message' => 'Error al reincorporar operador','color' => 'red','type' => 'error'
            ]);
        }   
    }

    public function personalAdministrativo(){
        //$operador = operador::with('direccion.asentamiento.municipio.estados','direccion.asentamiento.codigoPostal')->get();
        $codigoPostal = codigoPostal::all();
        $direccion = direccion::all();
        $escolaridad = escolaridad::all();
        $personal = personal::all();
        //$direccion = direccion::with('asentamiento.municipio.estados', 'asentamiento.codigoPostal')->get();
        // Aquí se ajusta el operadorDireccion a cada operador y agrega propiedades al operador
        $personal->each(function($personal) use ($direccion) {
            $domicilio = $direccion->where('idDireccion', $personal->idDireccion)->first();
            $personal->domicilio = $domicilio ? $domicilio->calle . " #" . $domicilio->numero . ", " . $domicilio->asentamiento->asentamiento . ", " . $domicilio->asentamiento->municipio->municipio . ", " . $domicilio->asentamiento->municipio->estados->entidad . ", " . $domicilio->asentamiento->codigoPostal->codigoPostal : null;
            $personal->calle = $domicilio ? $domicilio->calle : null;
            $personal->numero = $domicilio ? $domicilio->numero : null;
            $personal->codigoPostal = $domicilio ? $domicilio->asentamiento->codigoPostal->codigoPostal : null;
            $personal->idAsentamiento = $domicilio ? $domicilio->asentamiento->idAsentamiento : null;
            $personal->idMunicipio = $domicilio ? $domicilio->asentamiento->municipio->idMunicipio : null;
            $personal->idEntidad = $domicilio ? $domicilio->asentamiento->municipio->estados->idEntidad : null;
        });
        $empresa = empresa::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/PersonalAdministrativo',[
            'usuario' => $usuario,
            'empresa' => $empresa,
            'escolaridad' => $escolaridad,
            'codigoPostal' => $codigoPostal,
            'direccion' => $direccion,
            'personal' => $personal,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addPersonal(Request $request){
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'fechaNacimiento' => 'required',
                'edad' => 'required',
                'CURP' => 'required',
                'RFC' => 'required',
                'numTelefono' => 'required',
                'telEmergencia' => 'nullable',
                'NSS' => 'required',
                'escolaridadd' => 'required',
                'fechaAlta' => 'required',
                'fechaBaja' => 'nullable',
                'empresaa' => 'required',
                'antiguedad' => 'required',
                'numINE' => 'required',
                'vigenciaINE' => 'required',
                'constanciaSF' => 'nullable|boolean',
                'totalDiasVac' => 'required',
                'diasVacRestantes' => 'required',
            ]);
            //dd($request->all());
            // Verificar si el operador ya existe
            $existingPersonal = personal::where('CURP', $request->CURP)->first();
            if($existingPersonal){
            // Operador ya existe, puedes devolver una respuesta indicando el error
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "El personal ya está registrado: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, " con CURP:" . $request->CURP, "color" => "yellow", 'type' => 'info']);
            }
            //fechaFormateada
            $fechaFormateada = date('ymd', strtotime($request->fechaNacimiento));
            //Se guarda el domicilio del profesor
            $domicilio = new direccion();
            $domicilio->calle = $request->calle;
            $domicilio->numero = $request->numero;
            $domicilio->idAsentamiento = $request->asentamiento;
            $domicilio->save();
    
            $personal = new personal();
            $personal->nombre = $request->nombre;
            $personal->apellidoP = $request->apellidoP;
            $personal->apellidoM = $request->apellidoM;
            $personal->fechaNacimiento = $request->fechaNacimiento;
            $personal->edad = $request->edad;
            $personal->CURP = $request->CURP;               
            $personal->RFC = $request->RFC;
            $personal->numTelefono = $request->numTelefono;
            $personal->telEmergencia = $request->telEmergencia;
            $personal->NSS = $request->NSS;
            $personal->idEscolaridad = $request->escolaridadd;

            $personal->fechaAlta = $request->fechaAlta;
            $personal->fechaBaja = $request->fechaBaja;
            $personal->idEmpresa = $request->empresaa;//le puse empresaa como esta en mi formulario
            $personal->antiguedad = $request->antiguedad;

            $personal->idDireccion = $domicilio->idDireccion;

            $personal->numINE = $request->numINE;
            $personal->vigenciaINE = $request->vigenciaINE;
            $personal->constanciaSF = $request->constanciaSF ?? false;

            $personal->totalDiasVac = $request->totalDiasVac;
            $personal->diasVacRestantes = $request->diasVacRestantes;

            $nombreCompleto = $personal->apellidoP . ' ' . $personal->apellidoM . ' ' . $personal->nombre;
            $personal->nombre_completo = $nombreCompleto;

            $personal->save();
            
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "Personal agregado correctamente: $nombreCompleto", "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "Error al agregar al personal", "color" => "red", 'type' => 'error']);
        }
    }

    public function actualizarPersonal(Request $request, $idPersonal){
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'fechaNacimiento' => 'required',
                'edad' => 'required',
                'CURP' => 'required',
                'RFC' => 'required',
                'numTelefono' => 'required',
                'telEmergencia' => 'nullable',
                'NSS' => 'required',
                'escolaridadd' => 'required',
                'fechaAlta' => 'required',
                'fechaBaja' => 'nullable',
                'empresaa' => 'required',
                'antiguedad' => 'required',
                'numINE' => 'required',
                'vigenciaINE' => 'required',
                'constanciaSF' => 'nullable|boolean',
                'totalDiasVac' => 'required',
                'diasVacRestantes' => 'required',
            ]);
            // Encontrar el operador
            $personal = personal::find($idPersonal);
            if (!$personal) {
                return redirect()->route('rh.personalAdministrativo')->with(['message' => "Personal no encontrado", "color" => "red", 'type' => 'error']);
            }

            //fechaFormateada
            $fechaFormateada = date('ymd', strtotime($request->fechaNacimiento));
            //Se guarda el domicilio del profesor
            $domicilio = new direccion();
            $domicilio->calle = $request->calle;
            $domicilio->numero = $request->numero;
            $domicilio->idAsentamiento = $request->asentamiento;
            $domicilio->save();
    
            $personal->nombre = $request->nombre;
            $personal->apellidoP = $request->apellidoP;
            $personal->apellidoM = $request->apellidoM;
            $personal->fechaNacimiento = $request->fechaNacimiento;
            $personal->edad = $request->edad;
            $personal->CURP = $request->CURP;               
            $personal->RFC = $request->RFC;
            $personal->numTelefono = $request->numTelefono;
            $personal->telEmergencia = $request->telEmergencia;
            $personal->NSS = $request->NSS;
            $personal->idEscolaridad = $request->escolaridadd;

            $personal->fechaAlta = $request->fechaAlta;
            $personal->fechaBaja = $request->fechaBaja;
            $personal->idEmpresa = $request->empresaa;//le puse empresaa como esta en mi formulario
            $personal->antiguedad = $request->antiguedad;

            $personal->idDireccion = $domicilio->idDireccion;

            $personal->numINE = $request->numINE;
            $personal->vigenciaINE = $request->vigenciaINE;
            $personal->constanciaSF = $request->constanciaSF ?? false;

            $personal->totalDiasVac = $request->totalDiasVac;
            $personal->diasVacRestantes = $request->diasVacRestantes;

            $nombreCompleto = $personal->apellidoP . ' ' . $personal->apellidoM . ' ' . $personal->nombre;
            $personal->nombre_completo = $nombreCompleto;

            $personal->save();
            
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "Personal actualizado correctamente: $nombreCompleto", "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "Error al actualizar al personal", "color" => "red", 'type' => 'error']);
        }
    }

        public function eliminarPersonal($personalesIds)
    {
        try {
            // Convierte la cadena de IDs en un array
            $personalesIdsArray = explode(',', $personalesIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $personalesIdsArray = array_map('intval', $personalesIdsArray);
            // Elimina los operadores
            personal::whereIn('idPersonal', $personalesIdsArray)->delete();

            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "Personal eliminado correctamente", "color" => "green"]);
        } catch (Exception $e) {
            Log::error('Error al eliminar al operador:', ['error' => $e->getMessage()]);
            return redirect()->route('rh.personalAdministrativo')->with(['message' => "No se pudo eliminar al personal", "color" => "red"]);
        }
    }

    public function movimientos(){
        $operador = operador::all();
        $operadoresAlta = operador::where('idEstado', 1)->get();
        $operadoresBaja = operador::where('idEstado',2)->get();
        $operadoresIncapacidad = operador::where('idEstado', 3)->get();
        $movimiento = movimiento::all();
        $tipoMovimiento = tipoMovimiento::all();
        $estado = estado::all();
        $directivo = directivo::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/Movimientos',[
            'usuario' => $usuario,
            'estado' => $estado,
            'operador' => $operador,
            'directivo' => $directivo,
            'operadoresAlta' => $operadoresAlta,
            'operadoresBaja' => $operadoresBaja,
            'operadoresIncapacidad' => $operadoresIncapacidad,
            'tipoMovimiento' => $tipoMovimiento,
            'movimiento' => $movimiento,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addMovimiento(Request $request) {
        try {
            // Validar datos del formulario
            $request->validate([
                'fechaMovimiento' => 'required',
                'estado' => 'required',
                'operador' => 'required',
                'directivo' => 'required',
                'tipoMovimiento' => 'required',
            ]);
            // Obtener datos del formulario
            $fechaMovimiento = $request->input('fechaMovimiento');
            $estado = $request->input('estado');
            $operadorId = $request->input('operador');
            $directivoId = $request->input('directivo');
            $tipoMovimiento = $request->input('tipoMovimiento');
    
            // Obtener el operador seleccionado
            $operador = operador::find($operadorId);
            if (!$operador) {
                return redirect()->route('rh.movimientos')->with(['message' => "Operador no encontrado", "color" => "red", 'type' => 'error']);
            }

            // Obtener el directivo correspondiente
            $directivo = directivo::find($directivoId);
            if (!$directivo) {
                return redirect()->route('rh.movimientos')->with(['message' => "Socio/Prestador no encontrado", "color" => "red", 'type' => 'error']);
            }
    
            // Función para calcular el número máximo de operadores permitidos
            function maxOperadoresPermitidos($numUnidades) {
                return ceil($numUnidades * 1.2); // Para cada 5 unidades, 6 operadores
            }
    
            // Actualizar el estado y la fecha según el tipo de movimiento
            switch ($estado) {
                case 1: // Alta
                    // Verificar si se puede agregar un operador según las reglas
                    $maxOperadores = maxOperadoresPermitidos($directivo->numUnidades);
                    if ($directivo->numOperadores >= $maxOperadores) {
                        return redirect()->route('rh.movimientos')->with(['message' => "El Socio/Prestador ha alcanzado el límite de operadores permitido para su número de unidades. Unidades: {$directivo->numUnidades}, Operadores: {$directivo->numOperadores}.", "color" => "red", 'type' => 'error']);
                    }
                    $operador->idEstado = 1; // Estado de Alta
                    $operador->fechaAlta = $fechaMovimiento; // Actualizar fechaAlta
                    $directivo->numOperadores += 1; // Aumentar numOperadores
                    $directivo->save(); // Guardar cambios en el directivo
                    break;
                case 2: // Baja
                    $operador->idEstado = 2; // Estado de Baja
                    $operador->fechaBaja = $fechaMovimiento; // Actualizar fechaBaja

                    // Disociar el operador de la unidad si está asociado
                    $unidad = $operador->unidad;
                    if ($unidad) {
                        $unidad->operador()->dissociate(); // Disociar el operador de la unidad
                        $unidad->save(); // Guardar cambios en la unidad
                    }
                    
                    // Restar 1 de numOperadores
                    if ($directivo->numOperadores > 0) {
                        $directivo->numOperadores -= 1; // Evitar números negativos
                        $directivo->save(); // Guardar cambios en el directivo
                    }
                    break;
                default:
                    return redirect()->route('rh.movimientos')->with(['message' => "Estado no válido", "color" => "red", 'type' => 'error']);
            }
    
            // Asignar el directivo al operador
            $operador->idDirectivo = $directivoId;
            // Guardar cambios en el operador
            $operador->save();
    
            // Crear el registro del movimiento
            movimiento::create([
                'fechaMovimiento' => $fechaMovimiento,
                'idEstado' => $estado,
                'idOperador' => $operadorId,
                'idDirectivo' => $directivoId,
                'idTipoMovimiento' => $tipoMovimiento,
            ]);
    
            return redirect()->route('rh.movimientos')->with(['message' => "Movimiento realizado correctamente", "color" => "green", 'type' => 'success']);
        } catch (Exception $e) {
            return redirect()->route('rh.movimientos')->with(['message' => "Error al realizar movimiento", "color" => "red", 'type' => 'error']);
        }
    }    
    
    public function eliminarMovimiento($movimientosIds){
        try{
            // Convierte la cadena de IDs en un array
            $movimientosIdsArray = explode(',', $movimientosIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $movimientosIdsArray = array_map('intval', $movimientosIdsArray);
            // Elimina las materias
            movimiento::whereIn('idMovimiento', $movimientosIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.movimientos')->with(['message' => "Movimiento eliminado correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('rh.movimientos')->with(['message' => "No se pudo eliminar el movimiento", "color" => "red"]);
        }
    }

    public function formarUnidades(){
        $directivo = directivo::all();
        $unidad = unidad::all();
        $operador = operador::all();
        $ruta = ruta::all();
        $castigo = castigo::all();
        $corte = corte::all();
        $entrada = entrada::all();
        $rolServicio = rolServicio::all();
        $ultimaCorrida = ultimaCorrida::all();
        $tipoUltimaCorrida = tipoUltimaCorrida::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/FormarUnidades',[
            'usuario' => $usuario,
            'directivo' => $directivo,
            'unidad' => $unidad,
            'operador' => $operador,
            'ruta' => $ruta,
            'castigo' => $castigo,
            'corte' => $corte,
            'entrada' => $entrada,
            'rolServicio' => $rolServicio,
            'ultimaCorrida' => $ultimaCorrida,
            'tipoUltimaCorrida' => $tipoUltimaCorrida,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function reportes()
    {
        $directivo = directivo::all();
        $operador = operador::all(); 
        $tipoOperador = tipoOperador::all();
        $estado = estado::all();
        $unidad = unidad::all();
        $ruta = ruta::all();
        $tipoUltimaCorrida = tipoUltimaCorrida::all();
        $movimiento = movimiento::all();
        $tipoMovimiento = tipoMovimiento::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/Reportes',[
            'usuario' => $usuario,
            'directivo' => $directivo,
            'unidad' => $unidad,
            'operador' => $operador,
            'movimiento' => $movimiento,
            'tipoOperador' => $tipoOperador,
            'estado' => $estado,
            'ruta' => $ruta,
            'tipoUltimaCorrida' => $tipoUltimaCorrida,
            'tipoMovimiento' => $tipoMovimiento,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function vacaciones(){
        $personal = personal::all();
        $vacaciones = vacaciones::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('RH/Vacaciones',[
            'usuario' => $usuario,
            'personal' => $personal,
            'vacaciones' => $vacaciones,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addVacaciones(Request $request)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                'persona' => 'required|exists:personal,idPersonal',
                'motivo' => 'required',
                'numeroDias' => 'required|integer|min:1',
                'fechaInicio' => 'required|date',
                'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            ]);
            // Obtener el ID del personal y el número de días solicitados
            $idPersonal = $request->input('persona');
            $numeroDias = $request->input('numeroDias');
            // Obtener el personal y verificar los días restantes
            $personal = personal::findOrFail($idPersonal);
            // Obtener el nombre completo del personal para el mensaje de éxito
            $nombreCompleto = $personal->nombre_completo;
            // Verificar si los días restantes son insuficientes
            if ($personal->diasVacRestantes == 0) {
                return redirect()->route('rh.vacaciones')->with([
                    'message' => $nombreCompleto .' ha agotado sus días de vacaciones correspondientes al año.',
                    'color' => 'red',
                    'type' => 'error',
                ]);
            }
    
            if ($numeroDias > $personal->diasVacRestantes) {
                return redirect()->route('rh.vacaciones')->with([
                    'message' => 'El número de días solicitados por ' . $nombreCompleto . ' excede los días de vacaciones restantes (' . $personal->diasVacRestantes . ' días restantes).',
                    'color' => 'red',
                    'type' => 'error',
                ]);
            }
            // Crear un nuevo registro de vacaciones
            vacaciones::create([
                'motivo' => $request->input('motivo'),
                'numeroDias' => $numeroDias,
                'fechaInicio' => $request->input('fechaInicio'),
                'fechaFin' => $request->input('fechaFin'),
                'idPersonal' => $idPersonal,
            ]);
            // Actualizar los días restantes del personal
            $personal->diasVacRestantes -= $numeroDias;
            $personal->save();
            // Redirigir con un mensaje de éxito
            return redirect()->route('rh.vacaciones')->with([
                'message' => 'Vacaciones registradas correctamente para: ' . $nombreCompleto .
                             ' | Días de vacaciones: ' . $numeroDias . 
                             ' | Del ' . $request->input('fechaInicio') . 
                             ' al ' . $request->input('fechaFin'),
                'color' => 'green',
                'type' => 'success',
            ]); 
        } catch (\Exception $e) {
            // Manejo de errores
            return redirect()->route('rh.vacaciones')->with([
                'message' => 'Error al agregar las vacaciones: ' . $e->getMessage(),
                'color' => 'red',
                'type' => 'error',
            ]);
        }
    } 
    
    public function eliminarVacaciones($vacacionesIds){
        try{
            // Convierte la cadena de IDs en un array
            $vacacionesIdsArray = explode(',', $vacacionesIds);
            // Limpia los IDs para evitar posibles problemas de seguridad
            $vacacionesIdsArray = array_map('intval', $vacacionesIdsArray);
            // Elimina las materias
            vacaciones::whereIn('idVacaciones', $vacacionesIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('rh.vacaciones')->with(['message' => "Vacaciones eliminado correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('rh.vacaciones')->with(['message' => "No se pudo eliminar las vacaciones", "color" => "red"]);
        }
    }
}
