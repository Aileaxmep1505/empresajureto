{{--
   Tokens de interfaz compartidos por el tablero, el inventario y las
   operaciones del WMS. Un solo acento (el azul del panel), neutros apenas
   teñidos hacia ese mismo azul, bordes de 1px en vez de sombras, y color de
   estado solo en etiquetas y puntos, nunca como decoración.

   Se puede incluir más de una vez en la misma página sin problema.
--}}
<style>
  :root{
    /* Superficies y tinta */
    --ui-surface:        #ffffff;
    --ui-surface-2:      oklch(0.985 0.003 262);
    --ui-surface-3:      oklch(0.965 0.006 262);
    --ui-border:         oklch(0.925 0.008 262);
    --ui-border-strong:  oklch(0.87 0.012 262);
    --ui-ink:            oklch(0.23 0.02 262);
    --ui-ink-2:          oklch(0.38 0.018 262);
    --ui-muted:          oklch(0.49 0.018 262);   /* 5:1 sobre blanco */
    --ui-faint:          oklch(0.62 0.015 262);   /* solo iconos y separadores */

    /* Acento: acciones primarias, selección y estado activo. Nada más. */
    --ui-accent:         oklch(0.55 0.2 262);
    --ui-accent-hover:   oklch(0.49 0.2 262);
    --ui-accent-ink:     oklch(0.42 0.17 262);
    --ui-accent-soft:    oklch(0.965 0.022 262);
    --ui-accent-ring:    oklch(0.55 0.2 262 / .22);

    /* Estados */
    --ui-ok-ink:         oklch(0.43 0.11 155);
    --ui-ok:             oklch(0.62 0.15 155);
    --ui-ok-soft:        oklch(0.965 0.035 155);
    --ui-warn-ink:       oklch(0.47 0.11 70);
    --ui-warn:           oklch(0.75 0.15 75);
    --ui-warn-soft:      oklch(0.97 0.045 85);
    --ui-danger-ink:     oklch(0.47 0.17 27);
    --ui-danger:         oklch(0.6 0.2 27);
    --ui-danger-soft:    oklch(0.968 0.025 27);

    /* Forma y profundidad */
    --ui-r-sm: 6px;  --ui-r: 8px;  --ui-r-lg: 12px;
    --ui-shadow-xs: 0 1px 2px oklch(0.23 0.02 262 / .05);
    --ui-shadow-pop: 0 12px 32px oklch(0.23 0.02 262 / .14), 0 2px 6px oklch(0.23 0.02 262 / .06);

    /* Movimiento: corto y con salida suave */
    --ui-ease: cubic-bezier(.22, 1, .36, 1);
    --ui-fast: 140ms;

    /* Capas, por encima del cascarón del panel (sidebar / topbar) */
    --ui-z-sticky: 5;  --ui-z-pop: 60;  --ui-z-fab: 900;
    --ui-z-backdrop: 1100;  --ui-z-modal: 1200;  --ui-z-tip: 1300;
  }
</style>
