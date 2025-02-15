<script setup>
import Swal from 'sweetalert2';
import { ref, reactive } from 'vue';
import * as XLSX from 'xlsx';
import axios from 'axios';
import jsPDF from 'jspdf';
/* import 'jspdf-autotable'; */

const entradas = ref([]);

const props = defineProps({
    message: { String, default: '' },
    color: { String, default: '' },
    unidad: {
        type: Object,
        default: () => ({}),
    },
    operador: {
        type: Object,
        default: () => ({}),
    },
    entrada: {
        type: Object,
        default: () => ({}),
    },
    corte: {
        type: Object,
        default: () => ({}),
    },
    castigo: {
        type: Object,
        default: () => ({}),
    },
    ultimaCorrida: {
        type: Object,
        default: () => ({}),
    },
    tipoUltimaCorrida: {
        type: Object,
        default: () => ({}),
    },
});

const isLoading = ref(false);

const form = reactive({
    unidad: null, // Puedes inicializarlo con algún valor predeterminado si lo deseas
    operador: null
});

const fetchEntradas = async (idUnidad, periodo) => {
    let url = '';
    if (periodo.tipo === 'semana') {
        url = route('reportes.concentradoSemana', { idUnidad: idUnidad, semana: periodo.valor });
    } else if (periodo.tipo === 'mes' || typeof periodo === 'number') {
        url = route('reportes.concentradoMes', { idUnidad: idUnidad, mes: periodo.valor });
    } else if (periodo.tipo === 'anio') {
        url = route('reportes.concentradoAnio', { idUnidad: idUnidad, anio: periodo.valor });
    }

    try {
        const response = await axios.get(url);
        entradas.value = response.data;
        console.log('Datos consultados:',entradas.value);
    } catch (error) {
        /* console.error(error); */
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron obtener los datos',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
};

const generarArchivo = async (reporte, formato, idUnidad, periodoSeleccionado) => {
    // Validar que se haya seleccionado la unidad y el periodo
    if (!idUnidad || !periodoSeleccionado.tipo || !periodoSeleccionado.valor) {
        Swal.fire({
            title: 'Error',
            text: 'Por favor seleccione los parámetros para poder generar el archivo.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    let periodo = { tipo: reporte.periodoSeleccionado, valor: '' };
    if (reporte.periodoSeleccionado === 'semana') {
        periodo.valor = semanaSeleccionada;
    } else if (reporte.periodoSeleccionado === 'mes') {
        periodo.valor = mesSeleccionado;
    } else if (reporte.periodoSeleccionado === 'anio') {
        periodo.valor = anioSeleccionado;
    }

    // Mostrar el spinner de carga
    isLoading.value = true;

    try {
        await fetchEntradas(idUnidad, periodo);
        if (reporte.titulo === 'Concentrado') {
            if (formato === 'pdf') {
                generarPDF(reporte.titulo, periodo); // Pasa el objeto periodo completo
            } else if (formato === 'excel') {
                generarExcel(reporte.titulo, periodo);
            } else if (formato === 'imprimir') {
                imprimirReporte(reporte.titulo, periodo);
            }
        } else {
            Swal.fire({
                title: `Generar el reporte "${reporte.titulo}" en ${formato}`,
                text: 'Lógica para generar este tipo de reporte aquí',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error(error);
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron obtener los datos',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    } finally {
        // Ocultar el spinner de carga después de que termine el proceso
        isLoading.value = false;
    }
};

const generarPDF = (tipo, periodoSeleccionado) => {
    const periodoTexto = periodoSeleccionado.tipo === 'semana'
        ? `Semana ${periodoSeleccionado.valor}`
        : periodoSeleccionado.tipo === 'mes'
            ? months[periodoSeleccionado.valor - 1]
            : periodoSeleccionado.valor;

    const nombreArchivo = `${tipo}-${periodoTexto}.pdf`;
    // Crear una instancia de jsPDF
    const doc = new jsPDF('landscape');
    // Agregar título
    doc.setFontSize(12);
    doc.text(`Reporte de ${tipo} - Período: ${periodoTexto}`, 14, 20);
    // Configurar tabla
    // Configurar columnas y filas
    const columns = [
        { header: 'Ruta', dataKey: 'ruta' },
        { header: 'Fecha', dataKey: 'fecha' },
        { header: 'Numero Unidad', dataKey: 'numeroUnidad' },
        { header: 'Socio/Prestador', dataKey: 'socioPrestador' },
        { header: 'Hora Entrada', dataKey: 'horaEntrada' },
        { header: 'Tipo Entrada', dataKey: 'tipoEntrada' },
        { header: 'Extremo', dataKey: 'extremo' },
        { header: 'Operador', dataKey: 'operador' }
    ];
    // Formatear datos para jsPDF
    const rows = entradas.value.map(entry => ({
        ruta: entry.ruta?.nombreRuta || 'N/A',
        fecha: entry.created_at ? new Date(entry.created_at).toLocaleDateString() : 'N/A',
        numeroUnidad: entry.unidad?.numeroUnidad || 'N/A',
        socioPrestador: entry.directivo ? `${entry.directivo.nombre_completo}` : 'N/A',
        horaEntrada: entry.horaEntrada ? entry.horaEntrada.substring(0, 5) : 'N/A',
        tipoEntrada: entry.tipoEntrada || "Tarde",//Agregué retardo
        extremo: entry.extremo || 'N/A',
        operador: entry.operador ? `${entry.operador.nombre_completo}` : 'N/A'
    }));
    // Agregar la tabla al PDF
    doc.autoTable({
        head: [columns.map(col => col.header)],
        body: rows.map(row => columns.map(col => row[col.dataKey])),
        startY: 24, // Ajustar la posición vertical de la tabla
        styles: {
            fontSize: 10,
            cellPadding: 4,
            halign: 'center'
        },
    });
    // Agregar fecha de creación en el pie de página
    const fechaCreacion = new Date().toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    doc.setFontSize(10);
    doc.text(`Fecha de creación: ${fechaCreacion}`, 14, doc.internal.pageSize.height - 10);

    // Descargar el PDF
    doc.save(nombreArchivo);
};

const generarExcel = (tipo, periodoSeleccionado) => {
    let periodoTexto;
    if (periodoSeleccionado.tipo === 'semana') {
        periodoTexto = `Semana ${periodoSeleccionado.valor}`;
    } else if (periodoSeleccionado.tipo === 'mes') {
        periodoTexto = months[periodoSeleccionado.valor - 1];
    } else if (periodoSeleccionado.tipo === 'anio') {
        periodoTexto = periodoSeleccionado.valor;
    } else {
        periodoTexto = periodoSeleccionado.valor;
    }

    const nombreArchivo = `${tipo}-${periodoTexto}.xlsx`;

    // Cabecera del Excel
    const data = [['Ruta', 'Fecha', 'Núm. Unidad', 'Domingo', 'Socio/Prestador', 'Operador', 'Hora Entrada', 'Tipo Entrada', 'Ext', 'Hora Corte', 'Hora Regreso', 'Causa De Corte', 'Hora Inicio Castigo', 'Hora Fin Castigo', 'Castigo', 'Observaciones', 'Hora Inicio UC', 'Hora Fin UC', 'Tipo UC']];

    entradas.value.forEach(entry => {
        entry.entradas.forEach(entrada => {
            const ruta = entrada.ruta.nombreRuta || 'N/A';
            const fecha = entrada.created_at ? new Date(entrada.created_at).toLocaleDateString() : 'N/A';
            const numeroUnidad = entry.unidad || 'N/A';
            const directivo = entrada.directivo.nombre_completo || 'N/A';
            const operador = entrada.operador ? `${entrada.operador.nombre_completo}` : 'N/A';
            const horaEntrada = entrada.horaEntrada ? entrada.horaEntrada.substring(0, 5) : 'N/A';
            const tipoEntrada = entrada.tipoEntrada || '';
            const extremo = entrada.extremo || 'N/A';

            // Calcular "Domingo" con base en rolServicio
            let trabajaDomingo = ' ';
            entry.rolServicio.forEach(rol => {
                const fechaRol = new Date(rol.created_at);

                // Calcular el próximo domingo
                const proximoDomingo = new Date(fechaRol);
                proximoDomingo.setDate(fechaRol.getDate() + (7 - fechaRol.getDay()));

                // Si la fecha de entrada corresponde al próximo domingo, asignar trabajaDomingo
                const fechaEntrada = new Date(entrada.created_at);
                if (fechaEntrada.toDateString() === proximoDomingo.toDateString()) {
                    trabajaDomingo = rol.trabajaDomingo;
                }
            });

            // Inicializar variables para cortes y castigos
            let horaCorte = '';
            let horaRegreso = '';
            let causa = '';
            let horaInicioCastigo = '';
            let horaFinCastigo = '';
            let castigo = '';
            let observaciones = '';

            // Concatenar los cortes correspondientes en la misma fecha
            entry.cortes.forEach(corte => {
                const corteFecha = new Date(corte.created_at).toLocaleDateString();
                if (corteFecha === fecha) {
                    horaCorte += (horaCorte ? `, ${corte.horaCorte ? corte.horaCorte.substring(0, 5) : ''}` : corte.horaCorte ? corte.horaCorte.substring(0, 5) : '');
                    horaRegreso += (horaRegreso ? `, ${corte.horaRegreso ? corte.horaRegreso.substring(0, 5) : ''}` : corte.horaRegreso ? corte.horaRegreso.substring(0, 5) : '');
                    causa += (causa ? `, ${corte.causa || ''}` : corte.causa || '');
                }
            });

            // Concatenar los castigos correspondientes en la misma fecha
            entry.castigos.forEach(cast => {
                const castigoFecha = new Date(cast.created_at).toLocaleDateString();
                if (castigoFecha === fecha) {
                    horaInicioCastigo += (horaInicioCastigo ? `, ${cast.horaInicio ? cast.horaInicio.substring(0, 5) : ''}` : cast.horaInicio ? cast.horaInicio.substring(0, 5) : '');
                    horaFinCastigo += (horaFinCastigo ? `, ${cast.horaFin ? cast.horaFin.substring(0, 5) : ''}` : cast.horaFin ? cast.horaFin.substring(0, 5) : '');
                    castigo += (castigo ? `, ${cast.castigo || ''}` : cast.castigo || '');
                    observaciones += (observaciones ? `, ${cast.observaciones || ''}` : cast.observaciones || '');
                }
            });

            // Buscar la última corrida correspondiente a la misma fecha
            let horaInicioUC = '';
            let horaFinUC = '';
            let tipoUltimaCorrida = '';

            entry.ultimaCorridas.forEach(ultimaCorrida => {
                const ultimaCorridaFecha = new Date(ultimaCorrida.created_at).toLocaleDateString();
                if (ultimaCorridaFecha === fecha) {
                    horaInicioUC = ultimaCorrida.horaInicioUC ? ultimaCorrida.horaInicioUC.substring(0, 5) : '';
                    horaFinUC = ultimaCorrida.horaFinUC ? ultimaCorrida.horaFinUC.substring(0, 5) : '';
                    tipoUltimaCorrida = ultimaCorrida.tipo_ultima_corrida.tipoUltimaCorrida || '';
                }
            });

            // Agregar fila de datos
            data.push([ruta, fecha, numeroUnidad, trabajaDomingo, directivo, operador, horaEntrada, tipoEntrada, extremo, horaCorte, horaRegreso, causa, horaInicioCastigo, horaFinCastigo, castigo, observaciones, horaInicioUC, horaFinUC, tipoUltimaCorrida]);
        });
    });

    // Crear libro de Excel
    const workbook = XLSX.utils.book_new();
    const worksheet = XLSX.utils.aoa_to_sheet(data);

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte_Concentrado');
    XLSX.writeFile(workbook, nombreArchivo);
};

const reportes = [
    { titulo: 'Concentrado', periodo: 'semana', periodoSeleccionado: 'semana' },
];

const formatos = [
    { tipo: 'pdf', texto: 'Generar PDF', clase: 'bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded', icono: 'fa-solid fa-file-pdf' },
    { tipo: 'excel', texto: 'Generar Excel', clase: 'bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded', icono: 'fa-solid fa-file-excel' },
];

// Definir semanas
const weeks = Array.from({ length: 52 }, (_, i) => i + 1);

// Definir meses
const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
// Definir años a partir de 2024
const currentYear = new Date().getFullYear();
const startYear = 2024; // Año inicial deseado
const years = Array.from({ length: currentYear - startYear + 1 }, (_, i) => startYear + i);

let semanaSeleccionada = 1; // Por defecto, la primera semana
let mesSeleccionado = 1; // Por defecto, enero
let anioSeleccionado = currentYear; // Por defecto, el año actual

</script>

<template>
    <div v-for="reporte in reportes" :key="reporte.titulo" class="mb-4 bg-zinc-100 rounded-lg p-4">

        <h3 class="text-lg font-bold ">{{ reporte.titulo }}</h3>
        <div class="bg-gradient-to-r from-cyan-500 to-cyan-500 h-px mb-2"></div>
        <div class="flex flex-wrap gap-4 mb-3">
            <h2 class="font-semibold text-l pt-0">Buscar por: </h2>
            <div>
                <div>
                    <select name="unidad" id="unidad" v-model="form.unidad"
                        class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option disabled select value="">----- Unidad ----- </option>
                        <option value="todas">Todas las unidades</option>
                        <option v-for="carro in unidad" :key="carro.idUnidad" :value="carro.idUnidad">
                            {{ carro.numeroUnidad }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 mb-3">
            <div class="flex flex-wrap space-x-3 mb-2">
                <h2 class="font-semibold text-l pt-0">Periodo: </h2>
                <select v-model="reporte.periodoSeleccionado"
                    class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="semana">Semanal</option>
                    <option value="mes">Mensual</option>
                    <option value="anio">Anual</option>
                </select>
                <template v-if="reporte.periodoSeleccionado === 'semana'">
                    <select v-model="semanaSeleccionada"
                        class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option v-for="(week, index) in weeks" :key="index" :value="week">Semana {{ week }}</option>
                    </select>
                </template>
                <template v-else-if="reporte.periodoSeleccionado === 'mes'">
                    <select v-model="mesSeleccionado"
                        class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}
                        </option>
                    </select>
                </template>
                <template v-else-if="reporte.periodoSeleccionado === 'anio'">
                    <select v-model="anioSeleccionado"
                        class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </template>
            </div>
        </div>
        <div class="flex flex-wrap space-x-3">
            <button v-for="formato in formatos" :key="formato.tipo" :class="formato.clase"
                @click="generarArchivo(reporte, formato.tipo, form.unidad, { tipo: reporte.periodoSeleccionado, valor: reporte.periodoSeleccionado === 'semana' ? semanaSeleccionada : reporte.periodoSeleccionado === 'mes' ? mesSeleccionado : reporte.periodoSeleccionado === 'anio' ? anioSeleccionado : '' })">
                <i :class="formato.icono + ' mr-2 jump-icon'"></i> {{ formato.texto }}
            </button>

            <!-- Spinner de carga -->
            <div :class="['loading-overlay', { show: isLoading }]">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</template>


<style>
.jump-icon:hover i {
    transition: transform 0.2s ease-in-out;
    transform: translateY(-3px);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    /* Asegúrate de que esté por encima de otros elementos */
    visibility: hidden;
    /* Oculto por defecto */
    opacity: 0;
    transition: visibility 0s, opacity 0.3s;
}

/* Mostrar el overlay */
.loading-overlay.show {
    visibility: visible;
    opacity: 1;
}

/* Estilos del spinner */
.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.3);
    border-top: 5px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Animación del spinner */
@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>