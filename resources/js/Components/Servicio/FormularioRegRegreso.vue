<script setup>
import { useForm, put } from '@inertiajs/inertia-vue3';
import { ref, watch } from 'vue';
import Modal from '../Modal.vue';
import { route } from 'ziggy-js';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
        hora: String,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    corte: {
        type: Object,
        default: () => ({}),
    },
    unidad: {
        type: Object,
        default: () => ({}),
    },
    unidadesConOperador: {
        type: Object,
        default: () => ({}),
    },
    title: { type: String },
    modal: { type: String },
    op: { type: String },
})
const emit = defineEmits(['close']);

const form = useForm({
    idCorte: props.corte.idCorte,
    unidad: props.corte.idUnidad,
    horaRegreso: props.corte.horaRegreso,
});

watch(() => props.corte, async (newVal) => {
    form.idCorte = newVal.idCorte;
    form.unidad = newVal.unidad;
    form.horaRegreso = props.corte.horaRegreso;
}, { deep: true }
);

// Validación de los select 
const validateSelect = (selectedValue) => {
    if (selectedValue == undefined) {
        return false;
    }
    return true;
};

const unidadError = ref('');
const horaRegresoError = ref('');

//Funcion para cerrar el formulario
const close = async () => {
    emit('close');
    form.reset();
}

const save = async () => {
    horaRegresoError.value = validateSelect(form.horaRegreso) ? '' : 'Seleccione la hora de regreso';
    unidadError.value = validateSelect(form.unidad) ? '' : 'Seleccione una unidad';


    if (
        horaRegresoError.value || unidadError.value
    ) {
        return;
    }
    form.post(route('servicio.registrarHoraRegreso'), {
        onSuccess: () => {
            close()
            horaRegresoError.value = '';
            unidadError.value = '';
        }
    })
}
</script>
<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="mt-2 bg-white p-4 shadow rounded-lg">
            <form @submit.prevent="(op === '1' ? save() : update())">
                <div class="border-b border-gray-900/10 pb-12">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">{{ title }}</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600 mb-4">Rellene el formulario para poder registrar la
                        hora de regreso del corte de una unidad.
                    </p>
                    <div class="flex flex-wrap -mx-4">
                        <!-- Unidad con v-select -->
                        <div class="sm:col-span-2 w-56 px-4">
                            <label for="unidad" class="block text-sm font-medium leading-6 text-gray-900">Unidad</label>
                            <div class="mt-2">
                                <v-select
                                    v-model="form.unidad"
                                    :options="unidadesConOperador.map(carro => ({ label: carro.numeroUnidad, value: carro.idUnidad }))"
                                    placeholder="Seleccione la unidad"
                                    :reduce="unidad => unidad.value"
                                    class="w-full">
                                </v-select>
                            </div>
                            <div v-if="unidadError != ''" class="text-red-500 text-xs mt-1">{{ unidadError }}</div>
                        </div>
                        <div class="sm:col-span-2 px-4"> <!-- Definir el tamaño del cuadro de texto -->
                            <label for="horaRegreso" class="block text-sm font-medium leading-6 text-gray-900">Hora de
                                regreso <span class="text-red-500">*</span></label>
                            <div class="mt-2">
                                <input type="time" name="horaRegreso" :id="'horaRegreso' + op"
                                    v-model="form.horaRegreso" placeholder="Seleccione la hora de regreso"
                                    class="block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                            </div>
                            <div v-if="horaRegresoError != ''" class="text-red-500 text-xs mt-1">{{ horaRegresoError }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="button" :id="'cerrar' + op"
                        class="text-sm font-semibold leading-6 bg-red-500 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 text-white py-2 px-4 rounded"
                        data-bs.dismiss="modal" @click="close"><i class="fa-solid fa-ban"></i> Cancelar</button>
                    <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded"
                        :disabled="form.processing"> <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </Modal>
</template>