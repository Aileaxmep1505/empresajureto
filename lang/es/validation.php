<?php

return [
    'required'  => 'El campo :attribute es obligatorio.',
    'email'     => 'El campo :attribute debe ser un correo válido.',
    'min'       => ['string' => 'El campo :attribute debe tener al menos :min caracteres.'],
    'max'       => ['string' => 'El campo :attribute no debe exceder :max caracteres.'],
    'confirmed' => 'La confirmación de :attribute no coincide.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
    ],
];
