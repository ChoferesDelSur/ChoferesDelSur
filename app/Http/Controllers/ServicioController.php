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
use App\Models\incapacidad;
use App\Models\codigoPostal;
use App\Models\direccion;
use App\Models\empresa;
use App\Models\convenioPago;
use App\Models\castigo;
use App\Models\corte;
use App\Models\usuario;
use App\Models\tipoUsuario;
use App\Models\entrada;
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

class ServicioController extends Controller
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
        $usuario->tipoUsuario2 = $usuario->tipoUsuario->tipoUsuario;

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
            return Inertia::render('Servicio/Inicio',[
                'usuario' => $usuario,
                'message' => $message /* session('message') */,
                'color' => $color,
                'type' => session('type'),
            ]);
        }
        return Inertia::render('Servicio/Inicio',[
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

            return Inertia::render('Servicio/Perfil', [
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

                return redirect()->route('servicio.perfil')->With(["message" => "Contraseña actualizada correctamente, recuerde su contraseña: " . $usuario->contrasenia, "color" => "green",'type' => 'success']);
            }
            return redirect()->route('servicio.perfil')->With(["message" => "Contraseña actual incorrecta", "color" => "red",'type' => 'error']);
        } catch (Exception $e) {
            return redirect()->route('servicio.perfil')->With(["message" => "Error al actualizar contraseña", "color" => "red",'type' => 'error']);
            dd($e);
        }
    }

    public function rutas(){
        $ruta = ruta::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('Servicio/Rutas',[
            'usuario' => $usuario,
            'ruta' => $ruta,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addRuta(Request $request){
        try{
            $request->validate([
                'nombreRuta'=> 'required',
            ]);

             // Verificar si la ruta ya existe en la base de datos
            $existingRuta = ruta::where('nombreRuta', $request->nombreRuta)->first();

            if ($existingRuta) {
                // Si ya existe, maneja la situación como desees, por ejemplo, redirigir con un mensaje de error.
                return redirect()->route('servicio.rutas')->with(['message' => "La ruta ya está registrada: " . $request->nombreRuta, 'color' => 'yellow', 'type' => 'info']);
            }
    
            $ruta = new ruta();
            $ruta->nombreRuta = $request->nombreRuta;
            $ruta->save();
            return redirect()->route('servicio.rutas')->with(['message' => "Ruta agregado correctamente: " .$request->nombreRuta, 'color' => 'green', 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->back()->with(['message' => "Error al agregar la ruta: " . $e->getMessage(), 'color' => 'error', 'type' => 'error']);
        }
    }

    public function actualizarRuta(Request $request, $idRuta)
    {
        try{
            $request->validate([
                'nombreRuta' => 'required',
            ]);
            $ruta = ruta::find($idRuta);
            $ruta->nombreRuta = $request->nombreRuta;
            $ruta->save();

            return redirect()->route('servicio.rutas')->with(['message' => "Ruta actualizada correctamente: " . $request->nombreRuta, "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.rutas')->with(['message' => "La ruta no se actualizó correctamente: " . $request->nombreRuta, "color" => "reed"]);
        }
    }

    public function eliminarRuta($rutasIds){
        try{
            // Convierte la cadena de IDs en un array
            $rutasIdsArray = explode(',', $rutasIds);

            // Limpia los IDs para evitar posibles problemas de seguridad
            $rutasIdsArray = array_map('intval', $rutasIdsArray);

            // Elimina las materias
            ruta::whereIn('idRuta', $rutasIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('servicio.rutas')->with(['message' => "Ruta eliminada correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.rutas')->with(['message' => "No se pudo eliminar la ruta", "color" => "red"]);
        }
    }

    public function sociosPrestadores(){
        $directivo = directivo::all();
        $operador = operador::all();
        $tipDirectivo = tipoDirectivo::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('Servicio/SociosPrestadores',[
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
                return redirect()->route('servicio.sociosPrestadores')->with(['message' => "El directivo ya está registrado: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, "color" => "yellow", 'type' => 'info']);
            }
    
            $directivo = new directivo();
            $directivo->nombre = $request->nombre;
            $directivo->apellidoP = $request->apellidoP;
            $directivo->apellidoM = $request->apellidoM;
            $directivo->idTipoDirectivo = $request->tipDirectivo;
            $nombreCompleto =$directivo->apellidoP . ' ' . $directivo->apellidoM. ' ' . $directivo->nombre;
            $directivo->nombre_completo = $nombreCompleto;

            $directivo->save();
            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "Directivo agregado correctamente: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "Error al agregar al directivo", "color" => "red", 'type' => 'error']);
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

            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "Directivo actualizado correctamente: " . $nombreCompleto, "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "El directivo no se actualizó correctamente: " . $nombreCompleto, "color" => "reed"]);
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
            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "Directivo eliminada correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.sociosPrestadores')->with(['message' => "No se pudo eliminar al directivo", "color" => "red"]);
        }
    }

    public function operadores(){
        $operador = operador::all();
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

        // Ajustar el operador con la última incapacidad
    $operador->each(function ($operador) {
        $ultimaIncapacidad = incapacidad::where('idOperador', $operador->idOperador)
            ->orderBy('created_at', 'desc') // Ordenar por fecha de creación más reciente
            ->first();

        // Añadir la última incapacidad como una propiedad al operador
        $operador->ultimaIncapacidad = $ultimaIncapacidad;
    });
        $empresa = empresa::all();
        $convenioPago = convenioPago::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('Servicio/Operadores',[
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
                'tipoOperador' => 'required',
                'estado' => 'required',
                'directivo' => 'required',
            ]);
            // Verificar si el operador ya existe
            $existingOperador = operador::where('nombre', $request->nombre)
            ->where('apellidoP', $request->apellidoP)
            ->where('apellidoM', $request->apellidoM)
            ->first();

            if($existingOperador){
            // Operador ya existe, puedes devolver una respuesta indicando el error
            return redirect()->route('servicio.operadores')->with(['message' => "El operador ya está registrado: " .$request->nombre ." " .$request->apellidoP ." " .$request->apellidoM, "color" => "yellow", 'type' => 'info']);
            }
    
            $operador = new operador();
            $operador->nombre = $request->nombre;
            $operador->apellidoP = $request->apellidoP;
            $operador->apellidoM = $request->apellidoM;
            $operador->idTipoOperador = $request->tipoOperador;
            $operador->idEstado = $request->estado;
            $operador->idDirectivo = $request->directivo;

            $nombreCompleto = $operador->apellidoP . ' ' . $operador->apellidoM . ' ' . $operador->nombre;
            $operador->nombre_completo = $nombreCompleto;

            $operador->save();
            return redirect()->route('servicio.operadores')->with(['message' => "Operador agregado correctamente: $nombreCompleto", "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('servicio.operadores')->with(['message' => "Error al agregar al operador", "color" => "red", 'type' => 'error']);
        }
    }

    public function actualizarOperador(Request $request, $idOperador)
    {
        try{
            $request->validate([
                'nombre'=> 'required',
                'apellidoP'=> 'required',
                'apellidoM' => 'required',
                'tipoOperador' => 'required',
                'estado' => 'required',
                'directivo' => 'required',
            ]);
            $operador = operador::find($idOperador);
            $operador->nombre = $request->nombre;
            $operador->apellidoP = $request->apellidoP;
            $operador->apellidoM = $request->apellidoM;
            $operador->idTipoOperador = $request->tipoOperador;
            $operador->idEstado = $request->estado;
            $operador->idDirectivo = $request->directivo;

            $nombreCompleto = $operador->apellidoP . ' ' . $operador->apellidoM . ' ' . $operador->nombre;
            $operador->nombre_completo = $nombreCompleto;
            
            $operador->save();
            return redirect()->route('servicio.operadores')->with(['message' => "Operador actualizado correctamente: " . $request->nombre . " " . $request->apellidoP . " " . $request->apellidoM, "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.operadores')->with(['message' => "El operador no se actualizó correctamente: " . $requests->nombre, "color" => "reed"]);
        }
    }

    public function eliminarOperador($operadoresIds){
        try{
            // Convierte la cadena de IDs en un array
            $operadoresIdsArray = explode(',', $operadoresIds);

            // Limpia los IDs para evitar posibles problemas de seguridad
            $operadoresIdsArray = array_map('intval', $operadoresIdsArray);

            // Elimina las materias
            operador::whereIn('idOperador', $operadoresIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('servicio.operadores')->with(['message' => "Operador eliminado correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.operadores')->with(['message' => "No se pudo eliminar al operador", "color" => "red"]);
        }
    }

    public function unidades(){
        $unidad = unidad::all();
        $operador = operador::all(); 
        $directivo = directivo::all();
        
        // Obtener los operadores disponibles
        $operadoresDisp = operador::where('idEstado', 1) // Filtrar por estado "Alta"
                                   ->whereDoesntHave('unidad') // Verificar que no estén relacionados con ninguna unidad
                                   ->get();
        
        // Obtener las unidades disponibles (sin operador asignado)
        $unidadesDisp = unidad::whereNull('idOperador')->get();

        // Obtener las unidades que están relacionadas con algún operador
        $unidadesConOperador = unidad::whereNotNull('idOperador')->get();
        
        $ruta = ruta::all();
        $usuario = $this->obtenerInfoUsuario();
        
        return Inertia::render('Servicio/Unidades', [
            'usuario' => $usuario,
            'unidad' => $unidad,
            'operador' => $operador,
            'operadoresDisp' => $operadoresDisp,
            'unidadesDisp' => $unidadesDisp, // Pasar las unidades disponibles a la vista
            'unidadesConOperador' => $unidadesConOperador, // Pasar las unidades con operador a la vista
            'ruta' => $ruta,
            'directivo' => $directivo,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function addUnidad(Request $request){
        try{
            $request->validate([
                'numeroUnidad'=> 'required',
                'ruta' => 'required',
                'directivo' => 'required',
            ]);
            // Verificar si ya existe una unidad activa con los mismos datos
            $existingUnidad = unidad::where('numeroUnidad', $request->numeroUnidad)
                ->where('idRuta', $request->ruta)
                ->where('idDirectivo', $request->directivo)
                ->whereNull('deleted_at') // Excluir registros eliminados
                ->first();
            // Obtener el nombre completo del directivo y el nombre de la ruta
            $directivo = directivo::find($request->directivo);
            $nombredirectivo = $directivo ? $directivo->nombre_completo : 'Desconocido';

            $ruta = ruta::find($request->ruta);
            $nombreruta = $ruta ? $ruta->nombreRuta : 'Desconocida';

            if($existingUnidad){
                // Unidad ya existe, puedes devolver una respuesta indicando el error
                return redirect()->route('servicio.unidades')->with(['message' => "La unidad ya está registrada: " .$request->numeroUnidad ." - " .$nombreruta ." - " .$nombredirectivo, "color" => "yellow", 'type' => 'info']);
            }
            // Verificar si ya existe una unidad activa con el mismo número pero en otra ruta/directivo
            $existingNumero = unidad::where('numeroUnidad', $request->numeroUnidad)
            ->whereNull('deleted_at') // Excluir registros eliminados
            ->first();
        if($existingNumero){
            // Unidad ya existe con un número igual pero diferentes ruta y directivo
            return redirect()->route('servicio.unidades')->with(['message' => "Ya existe una unidad con el número proporcionado, pero con una ruta y directivo diferente: " .$request->numeroUnidad, "color" => "yellow", 'type' => 'info']);
        }
            $unidad = new unidad();
            $unidad->numeroUnidad = $request->numeroUnidad;
            $unidad->idRuta = $request->ruta;
            $unidad->idDirectivo = $request->directivo;
            // Verifica si se proporcionó un operador antes de asignarlo a la unidad
            if ($request->has('operador')) {
                $unidad->idOperador = $request->operador;
            }

            $unidad->save();

            if ($directivo) {
                $directivo->numUnidades += 1;
                $directivo->save();
            }
            
            return redirect()->route('servicio.unidades')->with(['message' => "Unidad agregada correctamente: " . $request->numeroUnidad, "color" => "green", 'type' => 'success']);
        } catch(Exception $e){
            return redirect()->route('servicio.unidades')->with(['message' => "Error al agregar la unidad " .$request->numeroUnidad, "color" => "red", 'type' => 'error']);
        }
    }

    public function actualizarUnidad(Request $request, $idUnidad)
    {
        try{
            $request->validate([
                'numeroUnidad'=> 'required',
                'ruta' => 'required',
                'directivo' => 'required',
            ]);
            $unidad = unidad::find($idUnidad);

            // Guardar el id del directivo actual antes de realizar cambios
            $directivoAnteriorId = $unidad->idDirectivo;
            $rutaAnteriorId = $unidad->idRuta;

            // Verificar si la combinación de número de unidad, ruta y directivo ya existe
            $existingUnidad = unidad::where('numeroUnidad', $request->numeroUnidad)
            ->where('idRuta', $request->ruta)
            ->where('idDirectivo', $request->directivo)
            ->where('idUnidad', '!=', $idUnidad)
            ->first();

            if ($existingUnidad) {
                return redirect()->route('servicio.unidades')->with([
                    'message' => "La combinación de número de unidad, ruta y directivo ya existe.",
                    "color" => "yellow",
                    'type' => 'info'
                ]);
            }

            $unidad->numeroUnidad = $request -> numeroUnidad;
            $unidad->idOperador = $request->operador;
            $unidad->idRuta = $request->ruta;
            $unidad->idDirectivo = $request->directivo;

            $unidad->save();

            // Verificar si la ruta ha cambiado
            if ($rutaAnteriorId != $request->ruta) {
                // Actualizar idRuta en registros de entrada, corte, castigo y ultimaCorrida para la unidad y fecha actual
                $hoy = Carbon::today();

                // Actualizar en tabla entrada
                entrada::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $hoy)
                    ->update(['idRuta' => $request->ruta]);

                // Actualizar en tabla corte
                corte::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $hoy)
                    ->update(['idRuta' => $request->ruta]);

                // Actualizar en tabla castigo
                castigo::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $hoy)
                    ->update(['idRuta' => $request->ruta]);

                // Actualizar en tabla ultimaCorrida
                ultimaCorrida::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $hoy)
                    ->update(['idRuta' => $request->ruta]);
            }

            // Verificar si el directivo ha cambiado
            if ($directivoAnteriorId != $request->directivo) {
                // Reducir numUnidades del directivo anterior
                $directivoAnterior = directivo::find($directivoAnteriorId);
                if ($directivoAnterior) {
                    $directivoAnterior->numUnidades -= 1;
                    $directivoAnterior->save();
                }

                // Aumentar numUnidades del nuevo directivo
                $nuevoDirectivo = directivo::find($request->directivo);
                if ($nuevoDirectivo) {
                    $nuevoDirectivo->numUnidades += 1;
                    $nuevoDirectivo->save();
                }
            }

            return redirect()->route('servicio.unidades')->with(['message' => "Unidad actualizado correctamente: " . $request->numeroUnidad, "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.unidades')->with(['message' => "La unidad no se actualizó correctamente: " . $requests->numeroUnidad, "color" => "reed"]);
        }
    }

    public function eliminarUnidad($unidadesIds){
        try{
                    // Convierte la cadena de IDs en un array
            $unidadesIdsArray = explode(',', $unidadesIds);

            // Limpia los IDs para evitar posibles problemas de seguridad
            $unidadesIdsArray = array_map('intval', $unidadesIdsArray);

            // Obtén las unidades a eliminar
            $unidades = unidad::whereIn('idUnidad', $unidadesIdsArray)->get();

            // Actualiza el numUnidades de los Directivos relacionados
            foreach ($unidades as $unidad) {
                if ($unidad->directivo) {
                    // Resta 1 al campo numUnidades del Directivo
                    $unidad->directivo->decrement('numUnidades');
                }
            }

            // Elimina las unidades
            unidad::whereIn('idUnidad', $unidadesIdsArray)->delete();
            // Redirige a la página deseada después de la eliminación
            return redirect()->route('servicio.unidades')->with(['message' => "Unidad eliminado correctamente", "color" => "green"]);
        }catch(Exception $e){
            return redirect()->route('servicio.unidades')->with(['message' => "No se pudo eliminar la unidad", "color" => "red"]);
        }
    }

    public function asignarOperador(Request $request)
    {
        try {
            // Obtener los IDs de la unidad y el operador del request
            $unidadId = $request->input('unidad');
            $operadorId = $request->input('operador');
            
            // Buscar la unidad y el operador en la base de datos
            $unidad = unidad::findOrFail($unidadId);
            $operador = operador::findOrFail($operadorId);
    
            // Asignar el operador a la unidad
            $unidad->idOperador = $operadorId; 
            $unidad->save();
    
            // Actualizar los registros de 'entrada' para hoy
            $entradasHoy = entrada::where('idUnidad', $unidadId)
                ->whereDate('created_at', Carbon::today())
                ->get();
    
            foreach ($entradasHoy as $entrada) {
                $entrada->idOperador = $operadorId;
                $entrada->save();
            }
    
            // Actualizar los registros de 'corte' para hoy
            $cortesHoy = corte::where('idUnidad', $unidadId)
                ->whereDate('created_at', Carbon::today())
                ->get();
    
            foreach ($cortesHoy as $corte) {
                $corte->idOperador = $operadorId;
                $corte->save();
            }
    
            // Actualizar los registros de 'castigo' para hoy
            $castigosHoy = castigo::where('idUnidad', $unidadId)
                ->whereDate('created_at', Carbon::today())
                ->get();
    
            foreach ($castigosHoy as $castigo) {
                $castigo->idOperador = $operadorId;
                $castigo->save();
            }
    
            // Actualizar los registros de 'ultimaCorrida' para hoy
            $ultimaCorridaHoy = ultimaCorrida::where('idUnidad', $unidadId)
                ->whereDate('created_at', Carbon::today())
                ->get();
    
            foreach ($ultimaCorridaHoy as $uc) {
                $uc->idOperador = $operadorId;
                $uc->save();
            }
    
            // Retornar un mensaje de éxito
            return redirect()->route('servicio.unidades')->with(['message' => 'Operador asignado correctamente a la unidad y actualizado en todos los registros.', "color" => "green", 'type' => 'success']);
        } catch (\Exception $e) {
            return redirect()->route('servicio.unidades')->with(['message' => 'Error al asignar operador: ' . $e->getMessage(), 'color' => 'red', 'type' => 'error']);
        }
    }    

    public function quitarOperador(Request $request)
    {
        try{
            // Obtener el ID de la unidad del request
        $unidadId = $request->input('unidad');

        // Buscar la unidad en la base de datos
        $unidad = unidad::findOrFail($unidadId);

        // Disociar el operador de la unidad
        $unidad->operador()->dissociate();
        $unidad->save();

        // Puedes retornar algún mensaje de éxito si lo deseas
        return redirect()->route('servicio.unidades')->with(['message' => 'Operador eliminado correctamente de la unidad.', "color" => "green", 'type' => 'success']);
        }catch(Exception $e){
            return redirect()->route('servicio.unidades')->with(['message' => 'Error al quitar operador', 'color' => 'red', 'type' => 'success']);
        }
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
        $usuario = $this->obtenerInfoUsuario();
        $entrada = entrada::all();
        $corte = corte::all();
        $ultimaCorrida = ultimaCorrida::all();
        $castigo = castigo::all();
        return Inertia::render('Servicio/Reportes',[
            'usuario' => $usuario,
            'unidad' => $unidad,
            'operador' => $operador,
            'tipoOperador' => $tipoOperador,
            'estado' => $estado,
            'ruta' => $ruta,
            'tipoUltimaCorrida' => $tipoUltimaCorrida,
            'entrada' => $entrada,
            'corte' => $corte,
            'ultimaCorrida' => $ultimaCorrida,
            'castigo' => $castigo,
            'message' => session('message'),
            'color' => session('color'),
            'type' => session('type'),
        ]);
    }

    public function formarUnidades(){
        $directivo = directivo::all();
        $unidad = unidad::all();
        // Obtener unidades que tienen operadores asignados
        $unidadesConOperador = unidad::whereNotNull('idOperador')
        ->with(['operador', 'ruta' => function($query) {
            $query->select('idRuta', 'nombreRuta'); // Solo selecciona idRuta y nombreRuta
        }])
        ->get();
        $operador = operador::all();
        $ruta = ruta::all();
        $castigo = castigo::all();
        $corte = corte::all();
        $entrada = entrada::all();
        $rolServicio = rolServicio::all();
        $ultimaCorrida = ultimaCorrida::all();
        $tipoUltimaCorrida = tipoUltimaCorrida::all();
        $usuario = $this->obtenerInfoUsuario();
        return Inertia::render('Servicio/FormarUnidades',[
            'usuario' => $usuario,
            'directivo' => $directivo,
            'unidad' => $unidad,
            'unidadesConOperador' => $unidadesConOperador,
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
    
    public function registrarHoraEntrada(Request $request)
    {
        try {
            // Obtener el ID de la unidad y la hora de entrada del formulario
            $unidadId = $request->input('unidad');
            $horaEntrada = Carbon::parse($request->input('horaEntrada'))->format('H:i'); // Formatear la hora
            $extremo = $request->input('extremo');
    
            /* // Verificar si la unidad existe
            $unidad = Unidad::find($unidadId); */
            // Verificar si la unidad existe y tiene un operador asignado
            $unidad = unidad::with('operador')->findOrFail($unidadId);
            if (!$unidad) {
                // La unidad no existe, puedes manejar el error aquí
                return redirect()->back()->with(['message' => "La unidad no existe", "color" => "yellow", 'type' => 'info']);
            }
    
            // Verificar si la unidad tiene un operador asignado
            if (!$unidad->operador) {
                // La unidad no tiene un operador asignado, puedes manejar el error aquí
                return redirect()->back()->with(['message' => "La unidad {$unidad->numeroUnidad} no tiene operador asignado", "color" => "yellow", 'type' => 'info']);
            }
    
            // Obtener el día de la semana
            $fecha = Carbon::now();
            $diaSemana = $fecha->dayOfWeek;
    
            // Definir los límites de tiempo según el día de la semana y el valor de extremo
            if ($diaSemana === 6 && $extremo === 'si') { // Sábado y extremo es 'si'
                $limiteNormal = Carbon::createFromTime(6, 45);
                $limiteMulta = Carbon::createFromTime(7, 0);//Quizá se quite porque no se considera multa
            } elseif ($diaSemana === 6) { // Sábado (sin considerar extremo)
                $limiteNormal = Carbon::createFromTime(6, 30);
                $limiteMulta = Carbon::createFromTime(7, 0);
            } elseif ($diaSemana === 0) { // Domingo
                $limiteNormal = Carbon::createFromTime(7, 30);
                $limiteMulta = Carbon::createFromTime(7, 45);
            } else { // Lunes a viernes
                $limiteNormal = Carbon::createFromTime(6, 15);
                $limiteMulta = Carbon::createFromTime(6, 30);
            }
    
            // Convertir la hora de entrada a un objeto Carbon
            $horaEntradaCarbon = Carbon::createFromFormat('H:i', $horaEntrada);
    
            // Determinar el tipo de entrada
            if ($horaEntradaCarbon < $limiteNormal) {
                $tipoEntrada = 'Normal';
            } elseif ($horaEntradaCarbon >= $limiteNormal && $horaEntradaCarbon <= $limiteMulta) {
                $tipoEntrada = 'Multa';
            } else {
                $tipoEntrada = '';
            }
    
            // Verificar si ya existe una entrada para esta unidad en el día actual
            $entradaExistente = entrada::where('idUnidad', $unidadId)
            ->whereDate('created_at', Carbon::today())
            ->first();

            if ($entradaExistente) {
                return redirect()->back()->with(['message' => "Ya está registrado la hora de entrada para la unidad {$unidad->numeroUnidad} el día de hoy", "color" => "yellow", 'type' => 'info']);
            }

            // Crear un nuevo registro en la tabla de entrada
            entrada::create([
                'idUnidad' => $unidadId,
                'horaEntrada' => $horaEntrada,
                'tipoEntrada' => $tipoEntrada,
                'extremo' => $extremo,
                'idOperador' => $unidad->operador->idOperador, // Registrar el ID del operador asociado
                'idRuta' => $unidad->ruta->idRuta,  // Registrar idRuta asociado
                'idDirectivo' => $unidad->directivo->idDirectivo,  // Registrar idDirectivo asociado
            ]);
    
            return redirect()->back()->with(['message' => "Hora de entrada {$horaEntrada} registrada correctamente para la unidad {$unidad->numeroUnidad}", "color" => "green", 'type' => 'success']);
        } catch (\Exception $e) {
            return redirect()->back()->with(['message' => "Error al registrar la hora de entrada", "color" => "red", 'type' => 'error']);
        }
    }

    public function registrarCorte(Request $request)
    {
            // Validar los datos recibidos del formulario
    $request->validate([
        'unidad' => 'required',
        'horaCorte' => 'required',
        'causa' => 'required',
    ]);

    try {
        // Obtener el ID de la unidad seleccionada del formulario
        $unidadId = $request->input('unidad');
        $unidad = unidad::find($unidadId);

        $numeroUnidad = $unidad->numeroUnidad;

        // Obtener el operador asociado con la unidad seleccionada
        $idOperador = $unidad->operador->idOperador;

        // Verificar si la unidad tiene registrada la hora de entrada en la tabla entradas
        $fechaActual = Carbon::now()->toDateString();

        // Usar created_at para verificar la fecha actual
        $entrada = entrada::where('idUnidad', $unidadId)
                          ->whereDate('created_at', $fechaActual)
                          ->first();

        if (!$entrada) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "La unidad " . $numeroUnidad . " no tiene registrada la hora de entrada el día de hoy.",
                'color' => 'yellow',
                'type' => 'info'
            ]);
        }

        // Depuración: Verificar el valor de horaEntrada
        if (!$entrada->horaEntrada) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "La unidad " . $numeroUnidad . " no tiene hora de entrada registrada.",
                'color' => 'yellow',
                'type' => 'info'
            ]);
        }

        // Validar que la horaRegreso no sea menor que la horaCorte, si se proporciona
        $horaCorte = \Carbon\Carbon::parse($request->input('horaCorte'));
        if ($request->has('horaRegreso')) {
            $horaRegreso = \Carbon\Carbon::parse($request->input('horaRegreso'));
            if ($horaRegreso->lessThan($horaCorte)) {
                $horaCorteFormateada = $horaCorte->format('H:i');
                $horaRegresoFormateada = $horaRegreso->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de regreso {$horaRegresoFormateada} no puede ser menor que la hora de corte {$horaCorteFormateada}.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
        }

        // Crear un nuevo registro de corte
        corte::create([
            'idUnidad' => $unidadId,
            'horaCorte' => $request->input('horaCorte'),
            'causa' => $request->input('causa'),
            'horaRegreso' => $request->input('horaRegreso'),
            'idOperador' => $idOperador, // Asociar el ID del operador
            'idRuta' => $unidad->ruta->idRuta,  // Registrar idRuta asociado
            'idDirectivo' => $unidad->directivo->idDirectivo,  // Registrar idDirectivo asociado
        ]);

        // Aquí puedes realizar otras acciones si es necesario, como enviar una respuesta JSON de éxito, etc.
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Hora de corte " . $request->horaCorte . " registrada correctamente para la unidad " . $numeroUnidad . " por " . $request->causa,
            "color" => "green",
            'type' => 'success'
        ]);
    } catch (Exception $e) {
        // Manejar cualquier error que pueda ocurrir durante la operación
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Error: " . $e->getMessage(),
            "color" => "red",
            'type' => 'error'
        ]);
    }
    }

    public function registrarCastigo(Request $request){
         // Validar los datos recibidos del formulario
    $validatedData = $request->validate([
        'unidad' => 'required|exists:unidad,idUnidad', // Asegúrate de que la unidad existe
        'castigo' => 'required|string|max:255',
        'horaInicio' => 'required',
        'horaFin' => 'nullable', // Asegúrate de que la horaFin sea posterior a la horaInicio
        'observaciones' => 'nullable|string|max:1000', // Observaciones opcionales
    ]);

    try {
        // Obtener el ID de la unidad seleccionada del formulario
        $unidadId = $validatedData['unidad'];
        $unidad = unidad::findOrFail($unidadId);
        $numeroUnidad = $unidad->numeroUnidad;

        // Obtener el operador, la ruta y el directivo asociados con la unidad seleccionada
        $operador = $unidad->operador;
        $ruta = $unidad->ruta;
        $directivo = $unidad->directivo;

        if (!$operador) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "No se puede registrar el castigo porque no se encontró un operador asociado con la unidad " . $numeroUnidad . ".",
                'color' => 'yellow',
                'type' => 'info'
            ]);
        }

        $idOperador = $operador->idOperador;
        $idRuta = $ruta->idRuta;
        $idDirectivo = $directivo->idDirectivo;

        // Verificar si la unidad tiene registrada la hora de entrada en la tabla entradas
        $fechaActual = Carbon::now()->toDateString();

        // Usar created_at para verificar la fecha actual
        $entrada = entrada::where('idUnidad', $unidadId)
                          ->whereDate('created_at', $fechaActual)
                          ->first();

        if (!$entrada) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "La unidad " . $numeroUnidad . " no tiene registrada la hora de entrada el día de hoy.",
                'color' => 'yellow',
                'type' => 'info'
            ]);
        }

        // Depuración: Verificar el valor de horaEntrada
        if (!$entrada->horaEntrada) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "La unidad " . $numeroUnidad . " no tiene hora de entrada registrada.",
                'color' => 'yellow',
                'type' => 'info'
            ]);
        }

        // Obtener la hora de inicio del request
        $horaInicio = \Carbon\Carbon::parse($request->input('horaInicio'));

        // Validar horaFin solo si está presente
        if ($request->has('horaFin')) {
            $horaFin = \Carbon\Carbon::parse($request->input('horaFin'));

            if ($horaFin->lessThanOrEqualTo($horaInicio)) {
                $horaInicioFormateada = $horaInicio->format('H:i');
                $horaFinFormateada = $horaFin->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de finalización del castigo " . $horaFinFormateada . " no puede ser igual o menor que la hora de inicio del castigo " . $horaInicioFormateada,
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
        }

        // Crear una nueva instancia del modelo castigo
        $nuevoCastigo = new castigo();
        $nuevoCastigo->idUnidad = $unidadId;
        $nuevoCastigo->castigo = $validatedData['castigo'];
        $nuevoCastigo->horaInicio = $validatedData['horaInicio'];

        // Solo asignar horaFin si se proporcionó
        if ($request->has('horaFin')) {
            $nuevoCastigo->horaFin = $request->input('horaFin');
        }
        $nuevoCastigo->observaciones = $validatedData['observaciones'] ?? '';
        $nuevoCastigo->idOperador = $idOperador; // Asociar el ID del operador
        $nuevoCastigo->idRuta = $idRuta; // Asociar el ID de la ruta
        $nuevoCastigo->idDirectivo = $idDirectivo; // Asociar el ID del directivo

        // Guardar el nuevo castigo en la base de datos
        $nuevoCastigo->save();

        return redirect()->route('servicio.formarUni')->with([
            'message' => "Castigo registrado correctamente para la unidad " . $numeroUnidad,
            'color' => 'green',
            'type' => 'success'
        ]);
    } catch (Exception $e) {
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Error: " . $e->getMessage(),
            'color' => 'red',
            'type' => 'error'
        ]);
    }
    }

        public function RegistrarFinCastigo(Request $request)
    {
        // Validar los datos recibidos del formulario
        $request->validate([
            'unidad' => 'required|exists:unidad,idUnidad', // Validar que la unidad exista
            'horaFin' => 'required', // Asegurarse de que se proporcione la hora de fin
        ]);

        try {
            // Obtener el ID de la unidad seleccionada del formulario
            $unidadId = $request->input('unidad');
            $unidad = unidad::findOrFail($unidadId);
            $numeroUnidad = $unidad->numeroUnidad;

            // Verificar si la unidad tiene un castigo registrado sin horaFin
            $castigo = castigo::where('idUnidad', $unidadId)
                            ->whereNull('horaFin') // Solo castigos sin hora de fin
                            ->latest() // Obtener el último castigo en curso
                            ->first();

            // Si no se encuentra un castigo activo, mostrar error
            if (!$castigo) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad {$numeroUnidad} no tiene castigos pendientes sin hora de finalización.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }

            // Verificar que la hora de fin no sea menor o igual a la hora de inicio
            $horaInicio = \Carbon\Carbon::parse($castigo->horaInicio);
            $horaFin = \Carbon\Carbon::parse($request->input('horaFin'));

            // Definir la hora fin formateada para usarla más adelante
            $horaFinFormateada = $horaFin->format('H:i');

            if ($horaFin->lessThanOrEqualTo($horaInicio)) {
                $horaInicioFormateada = $horaInicio->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de finalización del castigo {$horaFinFormateada} no puede ser igual o menor que la hora de inicio {$horaInicioFormateada}.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }

            // Actualizar la hora de fin en el castigo existente
            $castigo->horaFin = $horaFinFormateada;
            $castigo->save();

            // Respuesta de éxito
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Hora de finalización del castigo {$horaFinFormateada} registrada correctamente para la unidad {$numeroUnidad}.",
                'color' => 'green',
                'type' => 'success'
            ]);

        } catch (Exception $e) {
            // Manejar cualquier error que ocurra durante la operación
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error: " . $e->getMessage(),
                'color' => 'red',
                'type' => 'error'
            ]);
        }
    }

    public function RegistrarHoraRegreso(Request $request){
        $request->validate([
            'unidad' => 'required',
            'horaRegreso' => 'required',
        ]);

        try {
            $unidadId = $request->input('unidad');
            $unidad = unidad::find($unidadId);
            // Obtener el número de unidad para mostrarlo en el mensaje de éxito
            $numeroUnidad = $unidad->numeroUnidad;
    
            // Verificar si la unidad tiene una entrada de corte
            /* $corte = Corte::where('idUnidad', $unidadId)->latest()->first(); */

            // Verificar si la unidad tiene una entrada de corte para hoy
            $corte = corte::where('idUnidad', $unidadId)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

            if (!$corte) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "La unidad {$numeroUnidad} no tiene registrada hora de corte para hoy.",
                'color' => 'red',
                'type' => 'error'
            ]);
            }
    
            if ($corte) {
                // Verificar que la hora de regreso sea mayor o igual a la hora de corte
                $horaCorte = \Carbon\Carbon::parse($corte->horaCorte);
                $horaRegreso = \Carbon\Carbon::parse($request->input('horaRegreso'));
    
                if ($horaRegreso->lessThan($horaCorte)) {
                    $horaCorteFormateada = $horaCorte->format('H:i'); // Formatear la hora de corte
                    $horaRegresoFormateada = $horaRegreso->format('H:i'); // Formatear la hora de regreso
                    return redirect()->route('servicio.formarUni')->with(['message' => "La hora de regreso " . $horaRegresoFormateada . " no puede ser menor que la hora de corte " .$horaCorteFormateada, "color" => "yellow", 'type' => 'info']);
                }
    
                // Actualizar la hora de regreso
                $corte->horaRegreso = $request->input('horaRegreso');
                $corte->save();
            } else {
                // Si no existe un registro de corte para esta unidad, lanzar una excepción
                return redirect()->route('servicio.formarUni')->with(['message' => "La unidad " . $numeroUnidad . " no tiene registrado hora de corte", "color" => "red", 'type' => 'error']);
            }
    
            return redirect()->route('servicio.formarUni')->with(['message' => "Hora de regreso de la unidad " . $numeroUnidad . " registrado correctamente", "color" => "green", 'type' => 'success']);

        } catch(Exception $e){
            return redirect()->route('servicio.formarUni')->with(['message' => "Error al registrar la hora de regreso", "color" => "red", 'type' => 'error']);
        }
    }

    public function registrarUC(Request $request){
        $request->validate([
            'unidad' => 'required',
            'horaInicioUC' => 'required',
            'tipoUltimaCorrida' => 'required',
        ]);
    
        try {
            $unidadId = $request->input('unidad');
            $unidad = unidad::find($unidadId);
    
            $numeroUnidad = $unidad->numeroUnidad;
    
            // Obtener el operador asociado con la unidad seleccionada
            $operador = $unidad->operador;
    
            if (!$operador) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "No se encontró un operador asociado con la unidad " . $numeroUnidad . ".",
                    'color' => 'yellow',
                    'type' => 'info'
                ]);
            }
    
            // Obtener el idOperador, idRuta, idDirectivo de la unidad actual
            $idOperador = $unidad->operador->idOperador;
            $idRuta = $unidad->idRuta;
            $idDirectivo = $unidad->idDirectivo;
    
            // Verificar si la unidad tiene registrada la hora de entrada en la tabla entradas
            $fechaActual = Carbon::now()->toDateString();
    
            // Usar created_at para verificar la fecha actual
            $entrada = entrada::where('idUnidad', $unidadId)
                              ->whereDate('created_at', $fechaActual)
                              ->first();
    
            if (!$entrada) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad " . $numeroUnidad . " no tiene registrada la hora de entrada el día de hoy.",
                    'color' => 'yellow',
                    'type' => 'info'
                ]);
            }
    
            if (!$entrada->horaEntrada) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad " . $numeroUnidad . " no tiene hora de entrada registrada.",
                    'color' => 'yellow',
                    'type' => 'info'
                ]);
            }
    
            // Verificar si ya existe un registro de ultimaCorrida para hoy
            $registroExistente = ultimaCorrida::where('idUnidad', $unidadId)
                                              ->whereDate('created_at', $fechaActual)
                                              ->first();
    
            if ($registroExistente) {
                // Si ya existe un registro para la misma unidad el mismo día, impedir el registro
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad " . $numeroUnidad . " ya tiene registrada una última corrida el día de hoy.",
                    'color' => 'yellow',
                    'type' => 'info'
                ]);
            }
    
            // Validar que la horaFinUC no sea menor que la horaInicioUC, si se proporciona
            $horaInicioUC = \Carbon\Carbon::parse($request->input('horaInicioUC'));
            if ($request->has('horaFinUC')) {
                $horaFinUC = \Carbon\Carbon::parse($request->input('horaFinUC'));
                if ($horaFinUC->lessThan($horaInicioUC)) {
                    $horaInicioUCFormateada = $horaInicioUC->format('H:i');
                    $horaFinUCFormateada = $horaFinUC->format('H:i');
                    return redirect()->route('servicio.formarUni')->with([
                        'message' => "La hora de regreso $horaFinUCFormateada no puede ser menor que la hora de inicio de la corrida $horaInicioUCFormateada",
                        'color' => 'red',
                        'type' => 'error'
                    ]);
                }
            }
    
            // Crear un nuevo registro de ultimaCorrida
            ultimaCorrida::create([
                'idUnidad' => $unidadId,
                'horaInicioUC' => $request->input('horaInicioUC'),
                'horaFinUC' => $request->input('horaFinUC'),
                'idTipoUltimaCorrida' => $request->input('tipoUltimaCorrida'),
                'idOperador' => $idOperador,
                'idRuta' => $idRuta,
                'idDirectivo' => $idDirectivo,
            ]);
    
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Última corrida de la unidad " . $numeroUnidad . " registrada correctamente",
                'color' => 'green',
                'type' => 'success'
            ]);
    
        } catch(Exception $e) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error al registrar la última corrida de la unidad",
                'color' => 'red',
                'type' => 'error'
            ]);
        }
    }    

    public function RegistrarHoraRegresoUC(Request $request){
        // Validar los campos del formulario
        $request->validate([
            'unidad' => 'required',
            'horaFinUC' => 'required',
        ]);
    
        try {
            $unidadId = $request->input('unidad');
            $unidad = unidad::find($unidadId);
    
            if (!$unidad) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => 'Unidad no encontrada.',
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
    
            // Obtener el número de unidad para mostrarlo en el mensaje de éxito
            $numeroUnidad = $unidad->numeroUnidad;
    
            // Buscar el último registro de inicio de la UC para esta unidad hoy
            $ultimaCorrida = ultimaCorrida::where('idUnidad', $unidadId)
                ->whereDate('created_at', Carbon::today())
                ->orderBy('created_at', 'desc')
                ->first();
    
            if (!$ultimaCorrida) {
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad {$numeroUnidad} no tiene registrado hora de inicio de BN, UB o UC para hoy.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
    
            // Verificar si ya tiene una hora de regreso registrada
            if ($ultimaCorrida->horaFinUC) {
                // Si ya tiene hora de regreso, no permitir la actualización
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La unidad {$numeroUnidad} ya tiene registrada la hora de regreso para de la UC de hoy.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
    
            // Verificar que la hora de regreso sea mayor o igual a la hora de inicio de la UC
            $horaInicioUC = \Carbon\Carbon::parse($ultimaCorrida->horaInicioUC);
            $horaFinUC = \Carbon\Carbon::parse($request->input('horaFinUC'));
    
            if ($horaFinUC->lessThan($horaInicioUC)) {
                $horaInicioUCFormateada = $horaInicioUC->format('H:i');
                $horaFinUCFormateada = $horaFinUC->format('H:i');
        
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de regreso $horaFinUCFormateada no puede ser menor que la hora de inicio de la corrida $horaInicioUCFormateada",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
    
            // Si no hay hora de regreso, se puede actualizar la horaFinUC
            $ultimaCorrida->update([
                'horaFinUC' => $request->input('horaFinUC'),
            ]);
    
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Hora de regreso de UC de la unidad " . $numeroUnidad . " actualizada correctamente",
                'color' => 'green',
                'type' => 'success'
            ]);
        } catch (Exception $e) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error al registrar la hora de regreso de la UC",
                'color' => 'red',
                'type' => 'error'
            ]);
        }
    }
    
    public function registrarTrabajanDomingo(Request $request)
    {
        try {
            $unidadesSi = $request->input('unidadesSi', []);
            $unidadesNo = $request->input('unidadesNo', []);
    
            if (empty($unidadesSi) && empty($unidadesNo)) {
                throw new \Exception("No se han seleccionado unidades.");
            }
    
            \DB::transaction(function () use ($unidadesSi, $unidadesNo) {
                // Actualizar registros existentes en la tabla rolServicio para unidades que trabajan el domingo (SI)
                foreach ($unidadesSi as $unidadId) {
                    $rolServicio = rolServicio::where('idUnidad', $unidadId)->first();
                    if ($rolServicio) {
                        $rolServicio->update(['trabajaDomingo' => 'SI']);
                    } else {
                        rolServicio::create(['idUnidad' => $unidadId, 'trabajaDomingo' => 'SI']);
                    }
                }
            
                // Actualizar registros existentes en la tabla rolServicio para unidades que no trabajan el domingo (NO)
                foreach ($unidadesNo as $unidadId) {
                    $rolServicio = rolServicio::where('idUnidad', $unidadId)->first();
                    if ($rolServicio) {
                        $rolServicio->update(['trabajaDomingo' => 'NO']);
                    } else {
                        rolServicio::create(['idUnidad' => $unidadId, 'trabajaDomingo' => 'NO']);
                    }
                }
            });
            // Si no se lanzó ninguna excepción, la transacción se confirmará automáticamente
    
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Se han actualizado correctamente las unidades que trabajarán y no trabajarán el domingo.",
                'color' => 'green'
            ]);
        } catch (\Exception $e) {
            // Cualquier excepción lanzada dentro de la transacción hará que se reviertan automáticamente los cambios
    
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error: " . $e->getMessage(),
                'color' => 'red'
            ]);
        }
    }

        /* public function cambiarRolServicio(Request $request)
    {
        // Cambiar el valor de trabajaDomingo para todos los registros
        $rolServicios = rolServicio::all();

        foreach ($rolServicios as $rolServicio) {
            $rolServicio->trabajaDomingo = ($rolServicio->trabajaDomingo === 'SI') ? 'NO' : 'SI';
            $rolServicio->save();
        }

        return redirect()->route('servicio.formarUni')->with([
            'message' => "Rol de domingo actualizado correctamente",
            'color' => 'green'
        ]);
    } */

        public function cambiarRolServicio(Request $request)
    {
        try {
            // Obtener todas las unidades actuales
            $unidades = unidad::all();

            // Iterar sobre cada unidad y crear un nuevo registro en rolServicio
            foreach ($unidades as $unidad) {
                // Determinar el nuevo valor para trabajaDomingo
                $ultimoRegistro = rolServicio::where('idUnidad', $unidad->idUnidad)
                    ->latest('idRolServicio') // Tomar el registro más reciente
                    ->first();

                $nuevoValor = $ultimoRegistro && $ultimoRegistro->trabajaDomingo === 'SI' ? 'NO' : 'SI';

                // Crear un nuevo registro en rolServicio
                rolServicio::create([
                    'idUnidad' => $unidad->idUnidad,
                    'trabajaDomingo' => $nuevoValor,
                ]);
            }

            return redirect()->route('servicio.formarUni')->with([
                'message' => "Se han generado nuevos registros de rol de domingo para todas las unidades.",
                'color' => 'green',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error: " . $e->getMessage(),
                'color' => 'red',
            ]);
        }
    }

        public function actualizarRolRuta(Request $request)
    {
        // 1. Buscar las rutas por su nombre
        $rutaLibramiento = ruta::where('nombreRuta', 'LIBRAMIENTO - PLAZA DEL VALLE')->first();
        $rutaEsmeralda = ruta::where('nombreRuta', 'ESMERALDA - COL. JARDIN')->first();

        // Verificar que las rutas existen
        if (!$rutaLibramiento || !$rutaEsmeralda) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => 'Una o ambas rutas no se encontraron',
                'color' => 'red',
                'type' => 'error'
            ]);
        }

        // Buscar o crear una ruta temporal para evitar duplicados
        $rutaTemporal = ruta::firstOrCreate(['nombreRuta' => 'TEMPORAL']);


        // Actualizar las unidades
        unidad::where('idRuta', $rutaLibramiento->idRuta)
            ->update(['idRuta' => $rutaTemporal->idRuta]); // Cambia LIBRAMIENTO a ID temporal

        unidad::where('idRuta', $rutaEsmeralda->idRuta)
            ->update(['idRuta' => $rutaLibramiento->idRuta]); // Cambia ESMERALDA a LIBRAMIENTO

        unidad::where('idRuta', $rutaTemporal->idRuta)
            ->update(['idRuta' => $rutaEsmeralda->idRuta]); // Cambia el ID temporal a ESMERALDA

        // Actualizar registros de entrada, corte, castigo y última corrida del día actual
        $hoy = Carbon::now()->format('Y-m-d');

        // Actualizar ruta en los registros de entrada
        entrada::where('idRuta', $rutaLibramiento->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaTemporal->idRuta]);

        entrada::where('idRuta', $rutaEsmeralda->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaLibramiento->idRuta]);

        entrada::where('idRuta', $rutaTemporal->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaEsmeralda->idRuta]);

        // Realizar la misma actualización para la tabla corte
        corte::where('idRuta', $rutaLibramiento->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaTemporal->idRuta]);

        corte::where('idRuta', $rutaEsmeralda->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaLibramiento->idRuta]);

        corte::where('idRuta', $rutaTemporal->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaEsmeralda->idRuta]);

        // Realizar la misma actualización para la tabla castigo
        castigo::where('idRuta', $rutaLibramiento->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaTemporal->idRuta]);

        castigo::where('idRuta', $rutaEsmeralda->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaLibramiento->idRuta]);

        castigo::where('idRuta', $rutaTemporal->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaEsmeralda->idRuta]);

        // Realizar la misma actualización para la tabla ultimaCorrida
        ultimaCorrida::where('idRuta', $rutaLibramiento->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaTemporal->idRuta]);

        ultimaCorrida::where('idRuta', $rutaEsmeralda->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaLibramiento->idRuta]);

        ultimaCorrida::where('idRuta', $rutaTemporal->idRuta)
            ->whereDate('created_at', $hoy)
            ->update(['idRuta' => $rutaEsmeralda->idRuta]);
        // Eliminar la ruta temporal
        /* $rutaTemporal->delete(); */

        return redirect()->route('servicio.formarUni')->with([
            'message' => "Rol de rutas actualizadas correctamente",
            'color' => 'green',
            'type' => 'success'
        ]);
    }  
    
        public function obtenerRegistros($tipoRegistro, Request $request)
    {
        $idUnidad = $request->query('idUnidad'); // Obtiene el idUnidad de la solicitud
        $fechaActual = Carbon::today(); // Obtiene la fecha actual

        switch ($tipoRegistro) {
            case 'entradas':
                $registros = entrada::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $fechaActual) // Filtra por created_at
                    ->get();
                break;
            case 'cortes':
                $registros = corte::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $fechaActual) // Filtra por created_at
                    ->get();
                break;
            case 'castigos':
                $registros = castigo::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $fechaActual) // Filtra por created_at
                    ->get();
                break;
            case 'ultima_corrida':
                $registros = ultimaCorrida::where('idUnidad', $idUnidad)
                    ->whereDate('created_at', $fechaActual) // Filtra por created_at
                    ->get();
                break;
            default:
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "Tipo de registro no válido",
                    'color' => 'red',
                    'type' => 'error'
                ]);
        }

        return response()->json($registros);
    }

    public function actualizarEntrada(Request $request, $id)
    {
        try {
            // Obtener la entrada existente
            $entrada = entrada::findOrFail($id);

            // Obtener la unidad asociada
            $unidad = $entrada->unidad;
            
            // Obtener el día de la semana
            $fecha = Carbon::now();
            $diaSemana = $fecha->dayOfWeek;
    
            // Definir los límites de tiempo según el día de la semana y el valor de extremo
            if ($diaSemana === 6 && $request->input('extremo') === 'si') { // Sábado y extremo es 'si'
                $limiteNormal = Carbon::createFromTime(6, 45);
                $limiteMulta = Carbon::createFromTime(7, 0);
            } elseif ($diaSemana === 6) { // Sábado (sin considerar extremo)
                $limiteNormal = Carbon::createFromTime(6, 30);
                $limiteMulta = Carbon::createFromTime(7, 0);
            } elseif ($diaSemana === 0) { // Domingo
                $limiteNormal = Carbon::createFromTime(7, 30);
                $limiteMulta = Carbon::createFromTime(7, 45);
            } else { // Lunes a viernes
                $limiteNormal = Carbon::createFromTime(6, 15);
                $limiteMulta = Carbon::createFromTime(6, 30);
            }
    
            // Convertir la nueva hora de entrada a un objeto Carbon
            $horaEntradaNueva = Carbon::parse($request->input('horaEntrada'))->format('H:i');
            $horaEntradaCarbon = Carbon::createFromFormat('H:i', $horaEntradaNueva);
    
            // Determinar el nuevo tipo de entrada
            $tipoEntradaNuevo = '';
            if ($horaEntradaCarbon < $limiteNormal) {
                $tipoEntradaNuevo = 'Normal';
            } elseif ($horaEntradaCarbon >= $limiteNormal && $horaEntradaCarbon <= $limiteMulta) {
                $tipoEntradaNuevo = 'Multa';
            }
    
            // Actualizar los campos de la entrada
            $entrada->horaEntrada = $horaEntradaNueva;
            $entrada->extremo = $request->input('extremo');
            $entrada->tipoEntrada = $tipoEntradaNuevo; // Actualiza el tipo de entrada si es necesario
            $entrada->save();
    
            // Mensaje de éxito con los datos actualizados
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Entrada actualizada correctamente para la unidad {$unidad->numeroUnidad}, Formación: {$horaEntradaNueva}, Ext: {$entrada->extremo}",
            'color' => 'green',
            'type' => 'success'
        ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => "Error al actualizar la entrada: " . $e->getMessage(),
                'color' => 'red',
                'type' => 'error'
            ]);
        }
    }    

        public function actualizarCorte(Request $request, $id)
    {
        // Buscar el corte por ID
        $corte = corte::with('unidad')->findOrFail($id);
        
        // Validar que la horaRegreso no sea menor que la horaCorte, si se proporciona
        $horaCorte = Carbon::parse($request->input('horaCorte'));
        
        if ($request->has('horaRegreso')) {
            $horaRegreso = Carbon::parse($request->input('horaRegreso'));
            
            // Comprobar si la horaRegreso es menor que la horaCorte
            if ($horaRegreso->lessThan($horaCorte)) {
                $horaCorteFormateada = $horaCorte->format('H:i');
                $horaRegresoFormateada = $horaRegreso->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de regreso {$horaRegresoFormateada} no puede ser menor que la hora de corte {$horaCorteFormateada}.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
        }

        // Actualizar los campos del corte
        $corte->horaCorte = $request->input('horaCorte');
        $corte->causa = $request->input('causa');
        $corte->horaRegreso = $request->input('horaRegreso'); // Esto está bien, ya que puede ser null o un valor válido
        $corte->save();

        // Obtener el número de unidad
        $numeroUnidad = $corte->unidad->numeroUnidad;

        return redirect()->route('servicio.formarUni')->with([
            'message' => "Corte actualizado correctamente para la unidad {$numeroUnidad}.",
            'color' => 'green',
            'type' => 'success'
        ]);
    }

        public function actualizarCastigo(Request $request, $id)
    {
        // Buscar el castigo por ID
        $castigo = castigo::findOrFail($id);

        // Obtener la unidad asociada al castigo
        $unidad = $castigo->unidad; // Asumiendo la relación está definida
        $numeroUnidad = $unidad ? $unidad->numeroUnidad : 'desconocida';

        // Obtener la hora de inicio del request
        $horaInicio = \Carbon\Carbon::parse($request->input('horaInicio'));

        // Validar horaFin solo si está presente
        if ($request->has('horaFin')) {
            $horaFin = \Carbon\Carbon::parse($request->input('horaFin'));

            if ($horaFin->lessThanOrEqualTo($horaInicio)) {
                $horaInicioFormateada = $horaInicio->format('H:i');
                $horaFinFormateada = $horaFin->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de finalización del castigo {$horaFinFormateada} no puede ser igual o menor que la hora de inicio del castigo {$horaInicioFormateada}.",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
        }

        // Actualizar los datos del castigo
        $castigo->castigo = $request->input('castigo');
        $castigo->observaciones = $request->input('observaciones');
        $castigo->horaInicio = $request->input('horaInicio');

        // Actualizar horaFin solo si se proporciona
        if ($request->has('horaFin')) {
            $castigo->horaFin = $request->input('horaFin');
        }

        // Guardar los cambios
        $castigo->save();

        // Devolver mensaje de éxito incluyendo el número de unidad
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Castigo actualizado correctamente para la unidad " . $numeroUnidad,
            'color' => 'green',
            'type' => 'success'
        ]);
    }

    public function actualizarUltimaCorrida(Request $request, $id) {
        // Encontrar la última corrida por ID
        $ultimaCorrida = ultimaCorrida::findOrFail($id);
        // Validar los datos de entrada
        $request->validate([
            'horaInicioUC' => 'required',
            'horaFinUC' => 'nullable', // Esto permite que horaFinUC sea nulo
        ]);
    
        // Obtener horaInicioUC del request
        $horaInicioUC = \Carbon\Carbon::parse($request->input('horaInicioUC'));
    
        // Validar que horaFinUC no sea menor que horaInicioUC si se proporciona
        if ($request->has('horaFinUC')) {
            $horaFinUC = \Carbon\Carbon::parse($request->input('horaFinUC'));
            if ($horaFinUC->lessThan($horaInicioUC)) {
                $horaInicioUCFormateada = $horaInicioUC->format('H:i');
                $horaFinUCFormateada = $horaFinUC->format('H:i');
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "La hora de fin " . $horaFinUCFormateada . " no puede ser menor que la hora de inicio " . $horaInicioUCFormateada . ".",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }
        }
    
        // Actualizar los campos
        $ultimaCorrida->horaInicioUC = $horaInicioUC; // Asegurarse de que horaInicioUC se actualice correctamente
        $ultimaCorrida->horaFinUC = $request->input('horaFinUC'); // Se actualizará incluso si no se proporciona un valor
        $ultimaCorrida->idTipoUltimaCorrida = $request->input('tipoUltimaCorrida');
        $ultimaCorrida->save();
    
        // Obtener el numeroUnidad de la unidad relacionada
        $numeroUnidad = $ultimaCorrida->unidad->numeroUnidad; // Asegúrate de que la relación unidad esté definida en tu modelo
    
        return redirect()->route('servicio.formarUni')->with([
            'message' => "Última corrida actualizada correctamente para la unidad: $numeroUnidad",
            'color' => 'green',
            'type' => 'success'
        ]);
    }   
    
    public function eliminarRegistro($id, Request $request)
    {
        // Detectar el tipo de registro (puede que lo estés enviando desde el frontend)
        $tipoRegistro = $request->input('tipoRegistro'); // Suponiendo que 'tipoRegistro' se envía desde el formulario

        try {
            switch ($tipoRegistro) {
                case 'entradas':
                    $registro = entrada::findOrFail($id);
                    break;
                case 'cortes':
                    $registro = corte::findOrFail($id);
                    break;
                case 'castigos':
                    $registro = castigo::findOrFail($id);
                    break;
                case 'ultima_corrida':
                    $registro = ultimaCorrida::findOrFail($id);
                    break;
                default:
                return redirect()->route('servicio.formarUni')->with([
                    'message' => "Tipo de registro no válido",
                    'color' => 'red',
                    'type' => 'error'
                ]);
            }

            // Eliminar el registro
            $registro->delete();

            return redirect()->route('servicio.formarUni')->with([
                'message' => "Registro eliminado correctamente",
                'color' => 'green',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->route('servicio.formarUni')->with([
                'message' => "Error al eliminar registro",
                'color' => 'red',
                'type' => 'error'
            ]);
        }
    }
}