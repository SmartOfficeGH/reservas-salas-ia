# Identidad corporativa · Ajuntament de Palma

## Material revisado y recursos usados

Se han leído y renderizado ambas guías PDF (una página cada una), inspeccionado los logotipos del ZIP y analizado los 14 CSS del archivo 7z. Los materiales originales permanecen intactos en `RE__Estilo_corporativo_`; están excluidos de Git y del paquete. Las copias de inspección quedan en `.test-data`, también excluida.

- `Ajuntament de Palma identitat corporativa 2024.pdf`: ejemplos del logo azul sobre blanco y blanco sobre azul. Se utiliza únicamente el PNG azul oficial del ZIP, copiado sin modificar a `public/brand/ajuntament-palma-azul.png`. Se mantiene su proporción 709 × 246, fondo blanco y espacio alrededor; no se recorta, filtra, redibuja ni usa como fondo de fotografías.
- `Colores nueva marca logo Ayuntamiento.pdf`: azul #09548A, negro #191816, gris #7C8687 y turquesa #00B3CB. Azul para acciones, enlaces y ocupación; negro para texto; gris para bordes y turquesa como acento de foco. Los fondos claros y el azul de hover son derivados para legibilidad. El gris y el turquesa no se usan como texto pequeño sobre blanco por su contraste insuficiente.
- `css-sedipualba_v_1_0.7z`: Open Sans y Poppins Regular, ambas incrustadas como WOFF2 en `css-comun.css`. Se extraen sin alterar sus bytes para servirlas localmente. Open Sans en textos y Poppins en títulos. La declaración original dice peso 100, pero los metadatos de ambas fuentes indican Regular/400; se declara correctamente 400. No se suministran pesos negrita ni cursivas: el navegador sintetiza los énfasis. No se descargan fuentes externas en tiempo de ejecución.

## Compatibilidad de Sedipualba

Se adaptan los criterios de su capa Palma: variables de color, paneles claros, enlaces azules, selección de navegación, texto alineado a la izquierda y foco visible. El resultado está en `public/corporativo.css`, junto a los estilos estructurales existentes. No se importa el conjunto completo.

Los CSS contienen selectores para una estructura ajena a la aplicación (marcos, portal y trámites), reglas globales como `h1 { display:none }`, estilos de tablas, fuentes/iconos y rutas `/jscomun/`, `imgs/` y `fonts/` cuyos archivos no están incluidos. También incorporan un logotipo predeterminado de otra institución y una capa Palma con azul #00589F y magenta #FF1D74, distintos de la guía de colores entregada. Se prioriza la paleta del PDF y el logo del ZIP; no se incorporan esos recursos ni dependencias. No se altera la geometría temporal del calendario ni la lógica de sus controles.

## Licencias y límites de la referencia

Los archivos suministrados no contienen una licencia general de redistribución de los logos ni un manual tipográfico, tamaños mínimos o zona de protección cuantificada. Se usa el logo oficial para esta aplicación municipal, con espacio libre y sin atribuirle una licencia abierta. No se inventan normas ausentes.

Las fuentes incluyen avisos de copyright de 2020 de sus respectivos proyectos y referencia a SIL Open Font License. Se mantienen los metadatos incrustados y copias completas de OFL en `public/brand/`. Procedencia de las copias de licencia: repositorio oficial Google Fonts, `ofl/opensans/OFL.txt` y `ofl/poppins/OFL.txt`. No se redistribuye Font Awesome, icomoon ni las fuentes opcionales ausentes. Avisos complementarios en `public/brand/AVISOS.txt`.

## Revisión

El tema es común a acceso, registro, verificación, recuperación, agenda y Mis reservas. Conserva salas, aforos, mapas, fotos, carrusel sin avance automático, orden de página, calendario Día/Semana/Mes, formulario, normativa, permisos y validaciones. El paquete contiene recursos autónomos y no depende de los materiales originales.

Para revisar localmente: abrir http://127.0.0.1:8089/ y recargar con Ctrl+F5. Consultar PRUEBAS.md para resultados y límites de la revisión.

## Iconos de las fichas

Tres SVG locales en `public/icons/` (ubicación, personas e información), trazados geométricos propios de estilo común, sin librerías ni licencias externas. Trazo azul #09548A, 18 px y alineación con el comienzo del texto. Son decorativos: `alt=""` y `aria-hidden="true"`. Todas las etiquetas permanecen visibles.
