{{--
   Tokens de interfaz compartidos por el tablero, el inventario y las
   operaciones del WMS. Misma familia de color que el marco (Obsidiana):
   un solo azul de acento, neutros casi grises y color de estado solo en
   etiquetas y avisos, nunca como decoración.

   Claro y oscuro:
     - :root lleva el tema claro.
     - :root[data-theme="dark"] lo cambia a oscuro.
     - Si una pantalla no está preparada para oscuro, el layout marca su
       contenido con data-tema-fijo="claro" y aquí se vuelve a poner claro,
       para que nada quede con texto invisible.

   Se puede incluir más de una vez en la misma página sin problema.
--}}
<style>
  :root,
  :root[data-theme="dark"] [data-tema-fijo="claro"]{
    /* Superficies y tinta */
    --ui-surface:        #ffffff;
    --ui-surface-2:      #f7f8fa;
    --ui-surface-3:      #eff1f4;
    --ui-border:         #e6e8ec;
    --ui-border-strong:  #d5d9e0;
    --ui-ink:            #1d2330;
    --ui-ink-2:          #3a4250;
    --ui-muted:          #5f6776;   /* 5.6:1 sobre blanco */
    --ui-faint:          #9aa1ad;   /* solo iconos y separadores */

    /* Acento: acciones primarias, selección y estado activo. Nada más. */
    --ui-accent:         #2563eb;
    --ui-accent-hover:   #1d4ed8;
    --ui-accent-ink:     #1d4ed8;
    --ui-accent-soft:    #edf2ff;
    --ui-accent-ring:    rgba(37, 99, 235, .22);

    /* Estados */
    --ui-ok-ink:         #15803d;
    --ui-ok:             #16a34a;
    --ui-ok-soft:        #e8f8ee;
    --ui-warn-ink:       #a85a06;
    --ui-warn:           #f59e0b;
    --ui-warn-soft:      #fff5e0;
    --ui-danger-ink:     #c42b2b;
    --ui-danger:         #ef4444;
    --ui-danger-soft:    #ffeded;

    /* Globo de ayuda (tooltip): siempre contrasta con la página */
    --ui-tip-bg:         #1d2330;
    --ui-tip-ink:        #ffffff;

    /* Forma y profundidad */
    --ui-r-sm: 6px;  --ui-r: 8px;  --ui-r-lg: 12px;
    --ui-shadow-xs: 0 1px 2px rgba(17, 24, 39, .05);
    --ui-shadow-pop: 0 12px 32px rgba(17, 24, 39, .14), 0 2px 6px rgba(17, 24, 39, .06);

    /* Movimiento: corto y con salida suave */
    --ui-ease: cubic-bezier(.22, 1, .36, 1);
    --ui-fast: 140ms;

    /* Capas, por encima del marco (menú lateral y barra superior) */
    --ui-z-sticky: 5;  --ui-z-pop: 60;  --ui-z-fab: 900;
    --ui-z-backdrop: 1100;  --ui-z-modal: 1200;  --ui-z-tip: 1300;
  }

  /* Oscuro: misma paleta que el marco de Obsidiana */
  :root[data-theme="dark"]{
    --ui-surface:        #0f1a30;
    --ui-surface-2:      #0b1424;
    --ui-surface-3:      #16233d;
    --ui-border:         rgba(110, 150, 225, .16);
    --ui-border-strong:  rgba(130, 165, 235, .28);
    --ui-ink:            #e8eef8;
    --ui-ink-2:          #c4cfdf;
    --ui-muted:          #95a5bd;
    --ui-faint:          #6a7a93;

    --ui-accent:         #2563eb;
    --ui-accent-hover:   #3b74ee;
    --ui-accent-ink:     #79b4ff;
    --ui-accent-soft:    rgba(10, 132, 255, .16);
    --ui-accent-ring:    rgba(59, 116, 238, .40);

    --ui-ok-ink:         #5fdc8c;
    --ui-ok:             #22c55e;
    --ui-ok-soft:        rgba(34, 197, 94, .14);
    --ui-warn-ink:       #fbbf4a;
    --ui-warn:           #f59e0b;
    --ui-warn-soft:      rgba(245, 158, 11, .14);
    --ui-danger-ink:     #ff8a8a;
    --ui-danger:         #f25555;
    --ui-danger-soft:    rgba(239, 68, 68, .15);

    --ui-tip-bg:         #e8eef8;
    --ui-tip-ink:        #0b1424;

    --ui-shadow-xs: 0 1px 2px rgba(0, 0, 0, .35);
    --ui-shadow-pop: 0 16px 40px rgba(0, 0, 0, .55), 0 2px 8px rgba(0, 0, 0, .3);
  }
</style>
