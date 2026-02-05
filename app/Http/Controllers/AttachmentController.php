<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
  /**
   * Subir evidencias a cualquier modelo (polimórfico)
   * Ejemplo payload:
   * - attachable_type: App\Models\Expense
   * - attachable_id: 10
   * - files[]: (n archivos)
   */
  public function store(Request $request) {
    $data = $request->validate([
      'attachable_type' => ['required','string','max:255'],
      'attachable_id' => ['required','integer'],
      'files' => ['required'],
      'files.*' => ['file'], // SIN max aquí (pero el servidor sí manda)
    ]);

    $type = $data['attachable_type'];
    $id = (int)$data['attachable_id'];

    if (!class_exists($type)) {
      return response()->json(['message'=>'attachable_type inválido'], 422);
    }

    $model = $type::find($id);
    if (!$model || !method_exists($model, 'attachments')) {
      return response()->json(['message'=>'No se encontró el recurso o no soporta attachments'], 404);
    }

    $saved = [];
    foreach ((array)$request->file('files') as $file) {
      $path = $file->store('evidencias/'.date('Y/m'), 'public');

      $saved[] = $model->attachments()->create([
        'disk' => 'public',
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
        'size_bytes' => $file->getSize(),
        'uploaded_by' => auth()->id(),
      ]);
    }

    return response()->json(['ok'=>true,'attachments'=>$saved], 201);
  }

  public function destroy(Attachment $attachment) {
    if ($attachment->disk && $attachment->path) {
      Storage::disk($attachment->disk)->delete($attachment->path);
    }
    $attachment->delete();
    return response()->json(['ok'=>true]);
  }
}
