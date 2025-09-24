// categorias.js revisado

// Datos de categorías y subcategorías
const categorias = {
    1: { // Hombre
        usos: {
            2: ['Zapatillas deportivas', 'Botines de fútbol', 'Sandalias', 'Sneakers de moda'], // Calzado
            1: ['Camisetas', 'Pantalones deportivos', 'Sudaderas / Hoodies', 'Shorts'], // Ropa
            3: ['Gorras', 'Mochilas', 'Calcetines'] // Accesorios
        }
    },
    2: { // Mujer
        usos: {
            2: ['Zapatillas deportivas', 'Sandalias', 'Botines deportivos'],
            1: ['Tops deportivos', 'Leggings', 'Sudaderas', 'Shorts'],
            3: ['Gorras', 'Mochilas', 'Medias']
        }
    },
    3: { // Niños
        usos: {
            2: ['Botines', 'Sandalias'],
            1: ['Camisetas', 'Conjuntos deportivos', 'Shorts'],
            3: ['Mochilas', 'Gorras']
        }
    }
};

// Referencias a los selects
const generoSelect = document.getElementById('id_genero');
const usoSelect = document.getElementById('id_uso');
const subcategoriaSelect = document.getElementById('subcategoria');

// Función para limpiar select
function limpiarSelect(select, textoPredeterminado) {
    select.innerHTML = '';
    const option = document.createElement('option');
    option.value = '';
    option.textContent = textoPredeterminado;
    select.appendChild(option);
}

// Cuando cambia Género
generoSelect.addEventListener('change', () => {
    const genero = generoSelect.value;
    limpiarSelect(usoSelect, 'Seleccionar uso');
    limpiarSelect(subcategoriaSelect, 'Seleccionar subcategoría');

    if (categorias[genero]) {
        for (const idUso in categorias[genero].usos) {
            const option = document.createElement('option');
            option.value = idUso;
            // Nombre del uso según ID: puedes personalizar si quieres nombres diferentes
            let nombreUso = '';
            if (idUso == 1) nombreUso = 'Ropa';
            else if (idUso == 2) nombreUso = 'Calzado';
            else if (idUso == 3) nombreUso = 'Accesorios';
            option.textContent = nombreUso;
            usoSelect.appendChild(option);
        }
    }
});

// Cuando cambia Uso
usoSelect.addEventListener('change', () => {
    const genero = generoSelect.value;
    const uso = usoSelect.value;
    limpiarSelect(subcategoriaSelect, 'Seleccionar subcategoría');

    if (categorias[genero] && categorias[genero].usos[uso]) {
        categorias[genero].usos[uso].forEach(subcat => {
            const option = document.createElement('option');
            option.value = subcat;
            option.textContent = subcat;
            subcategoriaSelect.appendChild(option);
        });
    }
});
