<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
class UsuarioController extends Controller
{


    public function crearUsuario(Request $data)
    {
        try {
            DB::transaction(function () use ($data) {
                // Mapeo de datos del nuevo formato
                $email = trim($data['email']);
                $fullName = trim($data['fullName']);
                $documentNumber = trim($data['documentNumber']);
                $phone = trim(str_replace(['+', ' '], '', $data['phone'])); // Limpiar formato del teléfono
                $birthDate = $data['birthDate'];

                // Mapeo de género
                $gender = ($data['gender'] === 'male') ? 1 : 2;

                // Mapeo de tipo de documento
                $documentTypeMap = [
                    'dni' => 2,
                    'ruc' => 4,
                    'passport' => 3,
                    'other' => 1
                ];
                $documentType = $documentTypeMap[$data['documentType']] ?? 1;

                // Mapeo de fuente de referencia
                $referralSourceMap = [
                    'facebook' => 1,
                    'instagram' => 2,
                    'google' => 3,
                    'youtube' => 4,
                    'tiktok' => 5,
                    'other' => 6
                ];
                $referralSource = $referralSourceMap[$data['referralSource']] ?? 6;

                // Extraer código de país del teléfono (asumiendo formato +51 xxxxxxxx)
                $countryCode = 51; // Por defecto Perú
                if (str_starts_with($phone, '51')) {
                    $countryCode = 51;
                    $phone = substr($phone, 2);
                }

                // Calcular edad basada en fecha de nacimiento
                $age = Carbon::parse($birthDate)->age;

                // Verificar si el cliente ya existe por email
                $existingClient = DB::table('entidad')
                    ->select('ID_Entidad')
                    ->where('ID_Empresa', 1)
                    ->where('Nu_Tipo_Entidad', 0)
                    ->where('Txt_Email_Entidad', $email)
                    ->first();

                if ($existingClient) {
                    $ID_Entidad = $existingClient->ID_Entidad;
                } else {
                    // Crear nuevo cliente
                    $clientData = [
                        'ID_Empresa' => 1,
                        'ID_Organizacion' => 1,
                        'Nu_Tipo_Entidad' => 0, // 0=Cliente
                        'ID_Tipo_Documento_Identidad' => $documentType,
                        'Nu_Documento_Identidad' => $documentNumber,
                        'No_Entidad' => $fullName,
                        'Nu_Estado' => 1,
                        'Nu_Codigo_Pais' => $countryCode,
                        'Nu_Celular_Entidad' => $phone,
                        'Txt_Email_Entidad' => $email,
                        'Nu_Edad' => $age,
                        'Nu_Tipo_Sexo' => $gender,
                        'ID_Pais' => $data['country'],
                        'ID_Departamento' => !empty($data['department']) ? $data['department'] : 0,
                        'ID_Provincia' => !empty($data['province']) ? $data['province'] : 0,
                        'ID_Distrito' => !empty($data['district']) ? $data['district'] : 0,
                        'Nu_Curso' => 1,
                        'Fe_Nacimiento' => $birthDate,
                        'Nu_Como_Entero_Empresa' => $referralSource,
                        'No_Otros_Como_Entero_Empresa' => ($data['referralSource'] === 'other') ? 'Otros' : null,
                        'Txt_Rubro_Importacion' => 'Importación',
                        'Txt_Perfil_Compra' => 'Curso',
                        'Fe_Registro' => now(),
                    ];

                    $ID_Entidad = DB::table('entidad')->insertGetId($clientData);
                }

                // Generar credenciales de usuario
                $arrUsername = explode("@", $email);
                $username = $arrUsername[0];
                $password = strtoupper(substr($username, 0, 1)) . substr($username, 1) . date('Y') . date('m') . '$Pb';

                if (is_numeric($username)) {
                    $password_v2 = $arrUsername[1];
                    $password = strtoupper(substr($password_v2, 0, 1)) . substr($password_v2, 1) . date('Y') . date('m') . '$Pb';
                }

                // Verificar si el usuario ya existe
                $existingUser = DB::table('usuario')
                    ->select('ID_Usuario')
                    ->where('ID_Empresa', 1)
                    ->where('No_Usuario', $email)
                    ->first();

                if ($existingUser) {
                    $ID_Usuario = $existingUser->ID_Usuario;
                } else {
                    // Crear nuevo usuario
                    $userData = [
                        'ID_Empresa' => 1,
                        'ID_Organizacion' => 1,
                        'ID_Grupo' => 1205,
                        'No_Usuario' =>  $email,
                        'No_Nombres_Apellidos' => $fullName,
                        'No_Password' => Crypt::encryptString($password),
                        'Txt_Email' => $email,
                        'No_IP' => request()->ip(),
                        'Nu_Estado' => 1,
                        'ID_Entidad' => $ID_Entidad,
                        'Nu_Celular' => $phone,
                        'Fe_Creacion' => now(),
                        'Nu_Codigo_Pais' => $countryCode,
                    ];

                    $ID_Usuario = DB::table('usuario')->insertGetId($userData);

                    // Crear relación grupo-usuario
                    $groupUserData = [
                        'ID_Empresa' => 1,
                        'ID_Organizacion' => 1,
                        'ID_Grupo' => 1205,
                        'ID_Usuario' => $ID_Usuario,

                    ];

                    DB::table('grupo_usuario')->insert($groupUserData);
                }

                // Crear pedido de curso
                $courseOrderData = [
                    'ID_Empresa' => 1,
                    'ID_Organizacion' => 1,
                    'Nu_Estado' => 2, // 2=confirmado
                    'Nu_Estado_Usuario_Externo' => 3,
                    'Ss_Total' => 0,
                    'ID_Pais' => $data['country'],
                    'ID_Entidad' => $ID_Entidad,
                    'Fe_Emision' => now(),
                    'ID_Moneda' => 1,
                    'ID_Medio_Pago' => 2, // tarjeta de crédito
                    'Fe_Registro' => now(),
                ];

                $ID_Pedido_Curso = DB::table('pedido_curso')->insertGetId($courseOrderData);

                // Retornar resultado exitoso
                return [
                    'status' => 'success',
                    'message' => 'Usuario registrado, valida tu pago',
                    'result' => [
                        'id' => $ID_Pedido_Curso,
                        'email' => $email,
                        'password' => $password,
                        'name' => $fullName
                    ]
                ];
            });
            Log::info('Usuario creado correctamente'.json_encode($data));
            return [
                'status' => 'success',
                'message' => 'Usuario registrado, valida tu pago',
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            // Manejo de errores
            return [
                'status' => 'error',
                'message' => '¡Oops! Algo salió mal. Inténtalo mas tarde',
                'error' => $e->getMessage() // Solo para desarrollo, remover en producción
            ];
        }
    }

    // Función auxiliar para manejar errores de transacción
    public function crearUsuarioConManejorErrores($data)
    {
        try {
            return $this->crearUsuario($data);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '¡Oops! Algo salió mal. Inténtalo mas tarde',
                'error' => $e->getMessage() // Solo para desarrollo, remover en producción
            ];
        }
    }
    public function generateUsername($email)
    {
        $arrUsername = explode("@", $email);
        $username = $arrUsername[0];
        //concat random characters to the username
        $randomChars = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $username = $username . $randomChars;
        return $username;
    }
}
