function marcarError(campo, mensaje) {
    campo.classList.add('is-invalid');
    let aviso = campo.parentElement.querySelector('.invalid-feedback');
    if (!aviso) {
        aviso = document.createElement('div');
        aviso.className = 'invalid-feedback';
        campo.insertAdjacentElement('afterend', aviso);
    }
    aviso.textContent = mensaje;
}

function limpiarError(campo) {
    campo.classList.remove('is-invalid');
    const aviso = campo.parentElement.querySelector('.invalid-feedback');
    if (aviso) aviso.remove();
}

function textoObligatorio(campo, etiqueta) {
    return campo.value.trim() === '' ? etiqueta + ' es obligatorio.' : '';
}

function seleccionObligatoria(campo, etiqueta) {
    return campo.value === '' ? 'Debes seleccionar ' + etiqueta + '.' : '';
}

function entero(campo, etiqueta, minimo) {
    const v = campo.value.trim();
    if (v === '') return etiqueta + ' es obligatorio.';
    if (!/^\d+$/.test(v)) return etiqueta + ' debe ser un número entero, sin negativos ni decimales.';
    if (Number(v) < minimo) return etiqueta + ' debe ser mayor a cero.';
    return '';
}

function precio(campo) {
    const v = campo.value.trim();
    if (v === '') return 'El precio es obligatorio.';
    if (isNaN(v) || Number(v) < 0) return 'El precio no puede ser negativo.';
    return '';
}

// Reglas por formulario (la clave es el atributo "name" de cada campo)
const REGLAS = {
    producto: {
        codigo:       (c) => textoObligatorio(c, 'El código'),
        nombre:       (c) => textoObligatorio(c, 'El nombre'),
        id_categoria: (c) => seleccionObligatoria(c, 'una categoría'),
        precio:       (c) => precio(c),
        stock_actual: (c) => entero(c, 'El stock actual', 0),
        stock_minimo: (c) => entero(c, 'El stock mínimo', 0)
    },
    movimiento: {
        id_producto: (c) => seleccionObligatoria(c, 'un producto'),
        tipo:        (c) => seleccionObligatoria(c, 'un tipo de movimiento'),
        cantidad:    (c, form) => {
            const mensaje = entero(c, 'La cantidad', 1);
            if (mensaje) return mensaje;

            // RN4: una salida no puede superar el stock disponible
            const opcion = form.elements['id_producto'].selectedOptions[0];
            if (form.elements['tipo'].value === 'SALIDA' && opcion && opcion.dataset.stock !== undefined) {
                const stock = Number(opcion.dataset.stock);
                if (Number(c.value) > stock) return 'No hay stock suficiente. Stock actual: ' + stock + '.';
            }
            return '';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-validar]');
    if (!form) return;

    const reglas = REGLAS[form.dataset.validar];
    if (!reglas) return;

    const validarCampo = (nombre) => {
        const campo = form.elements[nombre];
        const mensaje = reglas[nombre](campo, form);
        if (mensaje) marcarError(campo, mensaje);
        else limpiarError(campo);
        return mensaje === '';
    };

    Object.keys(reglas).forEach((nombre) => {
        const campo = form.elements[nombre];
        campo.addEventListener('blur', () => validarCampo(nombre));
        campo.addEventListener('change', () => validarCampo(nombre));
        campo.addEventListener('input', () => {
            if (campo.classList.contains('is-invalid')) validarCampo(nombre);
        });
    });

    // En movimientos, cambiar producto o tipo obliga a revisar de nuevo la cantidad
    if (form.dataset.validar === 'movimiento') {
        ['id_producto', 'tipo'].forEach((nombre) => {
            form.elements[nombre].addEventListener('change', () => {
                if (form.elements['cantidad'].value.trim() !== '') validarCampo('cantidad');
            });
        });
    }

    form.addEventListener('submit', (e) => {
        let todoOk = true;
        Object.keys(reglas).forEach((nombre) => {
            if (!validarCampo(nombre)) todoOk = false;
        });
        if (!todoOk) {
            e.preventDefault();
            const primero = form.querySelector('.is-invalid');
            if (primero) primero.focus();
        }
    });
});