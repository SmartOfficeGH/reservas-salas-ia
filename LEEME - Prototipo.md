# Prototipo de Reservas Salas - IA

## Abrir

Abre `index.html` con un navegador (doble clic o «Abrir con»). No requiere instalación, servidor ni conexión a Internet. Mantén `index.html`, `estilos.css` y `prototipo.js` en la misma carpeta.

## Recorrido

1. Pulsa «Entrar como usuario de demostración» para simular el acceso de María, personal del Ayuntamiento.
2. Consulta la agenda diaria. Empieza en el día actual o en el siguiente lunes si es fin de semana, con tres reservas ficticias, una de ellas de María.
3. Selecciona una fecha y una sala. Rellena el concepto, la fecha y las horas y pulsa «Confirmar reserva».
4. La reserva se confirma si está dentro de lunes a viernes, de 07:00 a 16:00, termina después de empezar y no se solapa con otra de esa sala. Dos reservas pueden ser consecutivas.
5. Abre «Mis reservas» para consultar y cancelar las reservas de María. La cancelación libera el horario en la agenda.

## Limitaciones

- El acceso es una simulación: no acredita identidad ni conecta con cuentas corporativas o Microsoft Entra ID.
- Todos los nombres, conceptos y reservas son ficticios. Los conceptos se muestran a cualquier persona que entre en la demostración.
- Las reservas se guardan solo en memoria de esta página: al recargar se recuperan los ejemplos. No se comparten entre pestañas, navegadores o personas.
- No hay base de datos, servicios externos ni comprobación simultánea de reservas entre usuarios.
- Los límites de duración y antelación están pendientes; no se aplican restricciones adicionales al horario aprobado. Tampoco se excluyen festivos.
- No incluye las mejoras futuras del documento de definición ni constituye la aplicación real.
