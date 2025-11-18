<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();

        if($request->year) {
            $query->whereYear('fecha', $request->year);
        }
        if($request->month) {
            $query->whereMonth('fecha', $request->month);
        }
        if($request->day) {
            $query->whereDay('fecha', $request->day);
        }
        if($request->empresa) {
            $query->where('empresa', $request->empresa);
        }

        $posts = $query->latest()->paginate(10);

        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,pdf,doc,docx,xlsx,xls',
            'tipo' => 'required|in:foto,video,documento',
            'fecha' => 'required|date',
            'empresa' => 'nullable|string|max:255',
        ]);

        $archivoPath = $request->file('archivo')->store('public/posts');

        Post::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'archivo' => $archivoPath,
            'fecha' => $request->fecha,
            'empresa' => $request->empresa,
        ]);

        return redirect()->route('posts.index')->with('success', 'Publicación creada correctamente.');
    }

    public function show(Post $post)
    {
        $comentarios = $post->comentarios()->latest()->get();
        return view('posts.show', compact('post', 'comentarios'));
    }

    public function storeComment(Request $request, Post $post)
    {
        $request->validate([
            'usuario' => 'required|string|max:255',
            'comentario' => 'required|string',
        ]);

        $post->comentarios()->create($request->only('usuario', 'comentario'));

        return back()->with('success', 'Comentario agregado.');
    }
}
