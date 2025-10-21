<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Models\Comment;

// ✅ En Laravel 12 basta con esta clase Middleware (¡sin trait!)
use Illuminate\Routing\Controllers\Middleware;

class CommentController extends Controller
{
    /**
     * Registra middleware (Laravel 11/12).
     * Solo usuarios autenticados pueden crear y responder.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth', only: ['store', 'reply']),
        ];
    }

    /**
     * Lista comentarios de nivel raíz con sus respuestas.
     */
    public function index()
    {
        $comments = Comment::query()
            ->with([
                'user:id,name,email',
                'replies.user:id,name,email',
            ])
            ->whereNull('parent_id')
            ->latest()
            ->paginate(10);

        return view('web.comentarios', compact('comments'));
    }

    /**
     * Publicar comentario raíz.
     */
    public function store(Request $request)
    {
        $this->hitLimiter('comment.store');

        $data = $request->validate([
            'contenido' => ['required', 'string', 'min:2', 'max:4000'],
        ], [
            'contenido.required' => 'Escribe un comentario.',
        ]);

        $user = Auth::user();

        Comment::create([
            'user_id'   => $user->id,
            'nombre'    => $user->name ?? $user->email, // snapshot opcional
            'email'     => $user->email,
            'contenido' => $data['contenido'],
            'parent_id' => null,
        ]);

        return back()->with('status', '¡Gracias! Tu comentario se publicó.');
    }

    /**
     * Responder a un comentario (máximo 1 nivel).
     */
    public function reply(Request $request, Comment $comment)
    {
        $this->hitLimiter('comment.reply');

        $data = $request->validate([
            'contenido' => ['required', 'string', 'min:2', 'max:4000'],
        ], [
            'contenido.required' => 'Escribe tu respuesta.',
        ]);

        // Asegura que cuelgue del raíz (1 solo nivel)
        $parent = $comment->parent_id ? Comment::find($comment->parent_id) : $comment;

        $user = Auth::user();

        Comment::create([
            'user_id'   => $user->id,
            'nombre'    => $user->name ?? $user->email,
            'email'     => $user->email,
            'contenido' => $data['contenido'],
            'parent_id' => $parent->id,
        ]);

        return back()->with('status', 'Respuesta publicada.');
    }

    /**
     * Anti-spam simple con RateLimiter (5 intentos / 30s).
     */
    protected function hitLimiter(string $keySuffix): void
    {
        $user = Auth::user();
        $key = sprintf('comments:%s:%s', $keySuffix, $user?->id ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'contenido' => 'Demasiados intentos. Intenta de nuevo en unos segundos.',
            ]);
        }

        RateLimiter::hit($key, 30);
    }
}
