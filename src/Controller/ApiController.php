<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Repository\ApiKeysRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Chats;
use App\Entity\Mensajes;
use App\Entity\Invitaciones;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

final class ApiController extends AbstractController
{

    private const API_KEY = "a9F3kL2Qx7M8PZcR4eVYH6B5NwD1JmU0tS";
    
    #[Route('/api/ping', name: 'app_api_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'pong',
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }
    
    #[Route('/api/health', name: 'app_api_health', methods: ['GET'])]
    public function health(EntityManagerInterface $em): JsonResponse
    {
        $dbConnected = false;
        $dbError = null;
        $chatGeneral = null;
        $chatError = null;
        $debug = [];
        
        // Información de configuración
        $debug['env'] = $_ENV['APP_ENV'] ?? 'not_set';
        $debug['database_url_exists'] = isset($_ENV['DATABASE_URL']) ? 'yes' : 'no';
        $debug['database_url_prefix'] = isset($_ENV['DATABASE_URL']) ? substr($_ENV['DATABASE_URL'], 0, 25) . '...' : 'not_set';
        
        try {
            $connection = $em->getConnection();
            $params = $connection->getParams();
            $debug['driver'] = $params['driver'] ?? 'unknown';
            $debug['host'] = $params['host'] ?? 'unknown';
            $debug['port'] = $params['port'] ?? 'unknown';
            $debug['dbname'] = $params['dbname'] ?? 'unknown';
            $debug['unix_socket'] = isset($params['unix_socket']) ? $params['unix_socket'] : 'not_set';
            
            // Ejecutar query simple para probar conexión
            $result = $connection->executeQuery('SELECT 1 as test')->fetchOne();
            $dbConnected = ($result == 1);
            $debug['test_query_result'] = $result;
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
            $debug['error_class'] = get_class($e);
            $debug['error_file'] = $e->getFile();
            $debug['error_line'] = $e->getLine();
        }
        
        try {
            if ($dbConnected) {
                $chatGeneral = $em->getRepository(Chats::class)->findOneBy(['esGeneral' => true]);
            }
        } catch (\Exception $e) {
            $chatError = $e->getMessage();
        }
        
        return $this->json([
            'success' => $dbConnected,
            'message' => $dbConnected ? 'API Health Check' : 'Database connection failed',
            'data' => [
                'database' => $dbConnected ? 'connected' : 'disconnected',
                'databaseError' => $dbError,
                'chatGeneral' => $chatGeneral ? 'exists' : 'not found',
                'chatError' => $chatError,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ],
            'debug' => $debug
        ], $dbConnected ? 200 : 500);
    }
    
    #[Route('/api', name: 'app_api')]
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }

    #[Route('/api/documentacion', name: 'app_api_docs')]
    public function documentacion(): Response
    {
        return $this->render('api/documentacion.html.twig');
    }

    /*ENDPOINT API CONEXION* */
    #[Route('/api/conexion', name: 'app_api_conexion', methods: ['POST'])]
    public function conexion(Request $request): JsonResponse
    {
        $content = $request->getContent();
        if (empty($content)) {
            return $this->json([
                'message' => 'Empty request body',
                'status' => 400,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_request', 'message' => 'Request body must be JSON with apiKey']
            ], 400);
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'message' => 'Invalid JSON',
                'status' => 400,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_json', 'message' => 'Malformed JSON in request body']
            ], 400);
        }

        $apikey = isset($data['apikey']) ? trim($data['apikey']) : null;
        if ($apikey !== self::API_KEY) {
            return $this->json([
                'message' => 'Unauthorized '.$apikey,
                'status' => 401,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'unauthorized', 'message' => 'Invalid API key']
            ], 401);
        }

        return $this->json([
            'message' => 'Connection exitosa',
            'status' => 200,
            'success' => true,
            'data' => null,
            'error' => null
        ], 200);
    }

    /*ENDPOINT API LOGIN* */
    #[Route('/api/login', name: 'app_api_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepo, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $content = $request->getContent();
        if (empty($content)) {
            return $this->json([
                'message' => 'Empty request body',
                'status' => 400,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_request', 'message' => 'Request body must be JSON with email and password']
            ], 400);
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'message' => 'Invalid JSON',
                'status' => 400,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_json', 'message' => 'Malformed JSON in request body']
            ], 400);
        }

        $email = isset($data['email']) ? trim($data['email']) : null;
        $password = isset($data['password']) ? $data['password'] : null;

        if (empty($email) || empty($password)) {
            return $this->json([
                'message' => 'Missing credentials',
                'status' => 422,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'missing_fields', 'message' => 'Both email and password are required']
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'message' => 'Invalid email format',
                'status' => 422,
                'success' => false,
                'data' => null,
                'error' => ['code' => 'invalid_email', 'message' => 'Email is not a valid address']
            ], 422);
        }

        try {
            $user = $userRepo->findOneBy(['email' => $email]);
        } catch (\Exception $e) {
            error_log('Login DB error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => (object)[]
            ], 500);
        }
        
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Usuario o contraseña inválidos',
                'error' => (object)[]
            ], 401);
        }

        if (method_exists($user, 'isBaneado') && $user->isBaneado()) {
            return $this->json([
                'success' => false,
                'message' => 'Usuario baneado',
                'error' => (object)[]
            ], 403);
        }

        if (!$hasher->isPasswordValid($user, $password)) {
            return $this->json([
                'success' => false,
                'message' => 'Usuario o contraseña inválidos',
                'error' => (object)[]
            ], 401);
        }

        $nombreDeUsuario = $user->getNombre() ?: strstr($user->getEmail(), '@', true);

        return $this->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'nombreDeUsuario' => $nombreDeUsuario
            ]
        ], 200);
    }

    /*ENDPOINT API REGISTER* */
    #[Route('/api/register', name: 'app_api_register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepo, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['nombre', 'email', 'password']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['nombre', 'email', 'password']]
                ], 400);
            }

            $nombre = isset($data['nombre']) ? trim($data['nombre']) : null;
            $email = isset($data['email']) ? trim($data['email']) : null;
            $password = isset($data['password']) ? $data['password'] : null;

            // Validar campos obligatorios
            $missingFields = [];
            if (empty($nombre)) $missingFields[] = 'nombre';
            if (empty($email)) $missingFields[] = 'email';
            if (empty($password)) $missingFields[] = 'password';

            if (!empty($missingFields)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => $missingFields]
                ], 400);
            }

            // Validar longitud de campos
            $lengthErrors = [];
            if (strlen($nombre) < 3 || strlen($nombre) > 155) {
                $lengthErrors['nombre'] = 'Longitud no permitida';
            }
            if (strlen($email) > 180) {
                $lengthErrors['email'] = 'Longitud no permitida';
            }
            if (strlen($password) > 255) {
                $lengthErrors['password'] = 'Longitud no permitida';
            }

            if (!empty($lengthErrors)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Longitud de campo inválida',
                    'error' => $lengthErrors
                ], 400);
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'success' => false,
                    'message' => 'El correo electrónico no tiene un formato válido',
                    'error' => ['email' => 'Formato inválido']
                ], 400);
            }

            // Validar contraseña: mínimo 8 caracteres,
            $passwordErrors = [];
            
            if (strlen($password) < 8) {
                $passwordErrors[] = 'Debe tener al menos 8 caracteres';
            }
            
            if (!empty($passwordErrors)) {
                return $this->json([
                    'success' => false,
                    'message' => 'La contraseña no cumple con los requisitos',
                    'error' => ['password' => implode(', ', $passwordErrors)]
                ], 400);
            }

            // Verificar si el email ya existe
            $existingUserByEmail = $userRepo->findOneBy(['email' => $email]);
            if ($existingUserByEmail instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'El correo ya está asociado a una cuenta',
                    'error' => (object)[]
                ], 409);
            }

            // Verificar si el nombre ya existe
            $existingUserByNombre = $userRepo->findOneBy(['nombre' => $nombre]);
            if ($existingUserByNombre instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Este nombre ya existe',
                    'error' => (object)[]
                ], 409);
            }

            // Crear nuevo usuario
            $newUser = new User();
            $newUser->setEmail($email);
            $newUser->setNombre($nombre);
            $newUser->setPassword($hasher->hashPassword($newUser, $password));
            $newUser->setLatitud(0.0);
            $newUser->setLongitud(0.0);
            $newUser->setBaneado(false);
            $newUser->setActivo(true);
            $newUser->setFechaCreacion(new DateTime());
            
            // Generar token
            $token = bin2hex(random_bytes(32));
            $newUser->setToken($token);

            // Añadir automáticamente al chat general (si existe)
            try {
                $chatGeneral = $em->getRepository(Chats::class)->findOneBy(['esGeneral' => true]);
                if ($chatGeneral) {
                    $newUser->addChatPerteneciente($chatGeneral);
                }
            } catch (\Exception $e) {
                error_log('Warning: Could not add user to general chat: ' . $e->getMessage());
            }

            $em->persist($newUser);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Registro exitoso',
                'data' => [
                    'token' => $token,
                    'fechaCreacion' => $newUser->getFechaCreacion()?->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Register error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => (object)[]
            ], 500);
        }
    }


    /*ENDPOINT API LOGOUT* */
    #[Route('/api/logout', name: 'app_api_logout', methods: ['POST'])]
    public function logout(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Obtener el token del header Authorization o del body
            $token = null;
            
            // Intentar obtener del header Authorization: Bearer <token>
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                // Intentar obtener del body
                $content = $request->getContent();
                if (!empty($content)) {
                    $data = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($data['token'])) {
                        $token = trim($data['token']);
                    }
                }
            }

            // Validar que el token fue proporcionado
            if (empty($token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario requerido',
                    'error' => ['tokenUsuario' => 'Campo obligatorio']
                ], 400);
            }

            // Validar que el token tiene un formato válido (hexadecimal, 64 caracteres = 32 bytes)
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Buscar el usuario por token
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'No existe una sesión activa para el usuario',
                    'error' => (object)[]
                ], 404);
            }

            // Intentar cerrar las sesiones del usuario en sus chats
            try {
                // Obtener los chats del usuario
                $chats = $user->getChatPerteneciente();
                
                // Aquí puedes implementar lógica adicional para cerrar sesiones en chats
                // Por ahora, solo limpiar el token para cerrar la sesión
                // En una implementación más robusta, registraría el logout en una tabla de sesiones
                
            } catch (\Exception $chatError) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error en la salida del usuario',
                    'error' => ['chats' => 'No se pudieron cerrar todas las sesiones']
                ], 409);
            }

            // Limpiar el token del usuario (cerrar sesión)
            $user->setToken('');
            $em->persist($user);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'salida exitosa',
                'data' => (object)[]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API PERFIL* */
    #[Route('/api/perfil', name: 'app_api_perfil', methods: ['POST'])]
    public function perfil(Request $request, UserRepository $userRepo): JsonResponse
    {
        try {
            // Obtener el token del header Authorization o del body
            $token = null;
            
            // Intentar obtener del header Authorization: Bearer <token>
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                // Intentar obtener del body
                $content = $request->getContent();
                if (!empty($content)) {
                    $data = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($data['token'])) {
                        $token = trim($data['token']);
                    }
                }
            }

            // Validar que el token fue proporcionado (400 Bad Request)
            if (empty($token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario requerido',
                    'error' => [
                        'tokenUsuario' => 'Campo obligatorio'
                    ]
                ], 400);
            }

            // Validar que el token tiene un formato válido (401 Unauthorized)
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Buscar el usuario por token (404 Not Found - sesión no activa)
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'No existe una sesión activa para el usuario',
                    'error' => (object)[]
                ], 404);
            }

            // Obtener los datos del perfil
            try {
                $nombre = $user->getNombre() ?? '';
                $localización = $user->getLatitud() && $user->getLongitud() 
                    ? $user->getLatitud() . ', ' . $user->getLongitud() 
                    : '';
                $imagenAvatar = $user->getAvatar() ?? '';
                $biografia = $user->getBiografia() ?? '';
                $fechaCreacion = $user->getFechaCreacion() 
                    ? $user->getFechaCreacion()->format('Y-m-d H:i:s')
                    : '';

                // Retornar información del perfil (200 OK)
                return $this->json([
                    'success' => true,
                    'message' => 'Datos devueltos correctamente',
                    'data' => [
                        'nombre' => $nombre,
                        'localización' => $localización,
                        'imagenAvatar' => $imagenAvatar,
                        'biografia' => $biografia,
                        'fechaCreacion' => $fechaCreacion
                    ]
                ], 200);

            } catch (\Exception $e) {
                // Error al obtener campos (409 Conflict)
                return $this->json([
                    'success' => false,
                    'message' => 'No se han podido recuperar los datos del usuario',
                    'error' => [
                        'data' => 'Error al consultar la base de datos'
                    ]
                ], 409);
            }

        } catch (\Exception $e) {
            // Error inesperado (500 Internal Server Error)
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API EDITAR PERFIL* */
    #[Route('/api/perfil/editar', name: 'app_api_editar_perfil', methods: ['PUT'])]
    public function editarPerfil(Request $request, UserRepository $userRepo, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario requerido',
                    'error' => [
                        'tokenUsuario' => 'Campo obligatorio'
                    ]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario requerido',
                    'error' => [
                        'tokenUsuario' => 'Campo obligatorio'
                    ]
                ], 400);
            }

            // Obtener el token del header Authorization o del body
            $token = null;
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                if (isset($data['tokenUsuario'])) {
                    $token = trim($data['tokenUsuario']);
                }
            }

            // Si no se proporciona token, permitir buscar por 'nombre'
            $user = null;
            if (empty($token)) {
                $nombreSearch = isset($data['nombre']) ? trim($data['nombre']) : null;
                if (empty($nombreSearch)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Token de usuario requerido',
                        'error' => [
                            'tokenUsuario' => 'Campo obligatorio'
                        ]
                    ], 400);
                }

                // Buscar usuario por nombre
                $user = $userRepo->findOneBy(['nombre' => $nombreSearch]);
                if (!$user instanceof User) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado',
                        'error' => (object)[]
                    ], 404);
                }
            } else {
                // Validar formato del token (401 Unauthorized)
                if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Token inválido o expirado',
                        'error' => (object)[]
                    ], 401);
                }

                // Buscar usuario por token
                $user = $userRepo->findOneBy(['token' => $token]);
                if (!$user instanceof User) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado',
                        'error' => (object)[]
                    ], 404);
                }
            }

            // Obtener los campos a actualizar
            $nombre = isset($data['nombre']) ? trim($data['nombre']) : null;
            $avatar = isset($data['avatar']) ? trim($data['avatar']) : null;
            $biografia = isset($data['biografia']) ? trim($data['biografia']) : null;
            $passwordActual = isset($data['passwordActual']) ? $data['passwordActual'] : null;
            $passwordNueva = isset($data['passwordNueva']) ? $data['passwordNueva'] : null;

            // Validar biografía (máximo 255 caracteres)
            if ($biografia !== null && strlen($biografia) > 255) {
                return $this->json([
                    'success' => false,
                    'message' => 'La biografía excede la longitud permitida',
                    'error' => [
                        'biografia' => 'Máximo 255 caracteres'
                    ]
                ], 400);
            }

            // Si se quiere cambiar contraseña, validar passwordActual y passwordNueva
            if ($passwordNueva !== null) {
                // Validar que se proporcionó passwordActual
                if ($passwordActual === null) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Contraseña incorrecta',
                        'error' => [
                            'passwordActual' => 'No coincide con la registrada'
                        ]
                    ], 401);
                }

                // Validar que passwordActual es correcta
                if (!$hasher->isPasswordValid($user, $passwordActual)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Contraseña incorrecta',
                        'error' => [
                            'passwordActual' => 'No coincide con la registrada'
                        ]
                    ], 401);
                }

                // Validar requisitos de passwordNueva (mínimo 8 caracteres)
                if (strlen($passwordNueva) < 8) {
                    return $this->json([
                        'success' => false,
                        'message' => 'La nueva contraseña no cumple con los requisitos',
                        'error' => [
                            'passwordNueva' => 'Debe tener al menos 8 caracteres'
                        ]
                    ], 400);
                }

                // Hash de la nueva contraseña
                $hashedPassword = $hasher->hashPassword($user, $passwordNueva);
                $user->setPassword($hashedPassword);
            }

            // Actualizar campos opcionales
            if ($nombre !== null) {
                $user->setNombre($nombre);
            }
            if ($avatar !== null) {
                $user->setAvatar($avatar);
            }
            if ($biografia !== null) {
                $user->setBiografia($biografia);
            }

            // Intentar guardar cambios
            try {
                $em->persist($user);
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Datos actualizados correctamente',
                    'data' => (object)[]
                ], 200);

            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'No se pudieron actualizar los datos del usuario',
                    'error' => [
                        'data' => 'Error al persistir la información'
                    ]
                ], 409);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API HOME* */
    #[Route('/api/home', name: 'app_api_home', methods: ['POST'])]
    public function home(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario', 'latitud', 'longitud']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario', 'latitud', 'longitud']]
                ], 400);
            }

            $token = isset($data['tokenUsuario']) ? trim($data['tokenUsuario']) : null;
            $latitud = isset($data['latitud']) ? $data['latitud'] : null;
            $longitud = isset($data['longitud']) ? $data['longitud'] : null;

            // Validar campos obligatorios
            if (empty($token) || $latitud === null || $longitud === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario', 'latitud', 'longitud']]
                ], 400);
            }

            // Validar token
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario inválido',
                    'error' => null
                ], 401);
            }

            // Buscar usuario
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario inválido',
                    'error' => null
                ], 401);
            }

            // Delegar a actualizar con geolocalización
            $actualizarData = [
                'tokenUsuario' => $token,
                'geolocalizacion' => [
                    'latitud' => $latitud,
                    'longitud' => $longitud
                ]
            ];

            $actualizarRequest = new Request(
                [],
                [],
                [],
                [],
                [],
                [],
                json_encode($actualizarData)
            );
            $actualizarRequest->headers->set('Content-Type', 'application/json');

            return $this->actualizar($actualizarRequest, $userRepo, $em);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API ACTUALIZAR* */
    #[Route('/api/actualizar', name: 'app_api_actualizar', methods: ['POST'])]
    public function actualizar(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario']]
                ], 400);
            }

            $token = isset($data['tokenUsuario']) ? trim($data['tokenUsuario']) : null;
            
            // Validar token obligatorio
            if (empty($token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario']]
                ], 400);
            }

            // Validar formato de token
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario inválido',
                    'error' => null
                ], 401);
            }

            // Buscar usuario
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token de usuario inválido',
                    'error' => null
                ], 401);
            }

            // CASO A: Actualización desde Home (con geolocalización)
            if (isset($data['geolocalizacion'])) {
                $geo = $data['geolocalizacion'];
                
                if (!isset($geo['latitud']) || !isset($geo['longitud'])) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Datos incompletos',
                        'error' => ['fields' => ['geolocalizacion.latitud', 'geolocalizacion.longitud']]
                    ], 400);
                }

                // Actualizar ubicación del usuario
                $user->setLatitud((float)$geo['latitud']);
                $user->setLongitud((float)$geo['longitud']);
                $em->persist($user);
                $em->flush();

                // Buscar usuarios cercanos (dentro de 5 km)
                $usuariosCercanos = $this->findNearbyUsers($userRepo, $user, 5.0);

                // Obtener invitaciones pendientes
                $invitaciones = $this->getUserInvitations($user, $em);

                return $this->json([
                    'success' => true,
                    'message' => 'Usuarios cargados correctamente',
                    'data' => [
                        'usuarios' => $usuariosCercanos,
                        'invitaciones' => $invitaciones
                    ]
                ], 200);
            }

            // CASO B: Actualización de Chats
            if (isset($data['id_Chat']) && isset($data['id_ultimoMensaje'])) {
                $idChat = (int)$data['id_Chat'];
                $idUltimoMensaje = (int)$data['id_ultimoMensaje'];

                // Validar que el chat existe
                $chat = $em->getRepository(Chats::class)->find($idChat);
                if (!$chat) {
                    return $this->json([
                        'success' => false,
                        'message' => 'El chat no existe',
                        'error' => ['id_Chat' => 'No válido']
                    ], 404);
                }

                // Validar que el usuario pertenece al chat
                if (!$chat->getUsers()->contains($user)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'No tienes acceso a este chat',
                        'error' => (object)[]
                    ], 403);
                }

                // Obtener mensajes nuevos del chat
                $mensajes = $this->getNewMessages($idChat, $idUltimoMensaje, $em);

                // Obtener usuarios del chat
                $usuarios = $this->getChatUsers($idChat, $em);

                // Obtener invitaciones
                $invitaciones = $this->getUserInvitations($user, $em);

                return $this->json([
                    'success' => true,
                    'message' => 'Mensajes cargados correctamente',
                    'data' => [
                        'usuarios' => $usuarios,
                        'mensajes' => $mensajes,
                        'invitaciones' => $invitaciones
                    ]
                ], 200);
            }

            // Si no es ninguno de los dos casos
            return $this->json([
                'success' => false,
                'message' => 'Datos incompletos',
                'error' => ['fields' => ['geolocalizacion o id_Chat/id_ultimoMensaje']]
            ], 400);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    // Método auxiliar para encontrar usuarios cercanos
    private function findNearbyUsers(UserRepository $userRepo, User $currentUser, float $radiusKm): array
    {
        $allUsers = $userRepo->findAll();
        $nearbyUsers = [];

        foreach ($allUsers as $user) {
            if ($user->getId() === $currentUser->getId()) {
                continue; // Saltar al usuario actual
            }

            if (!$user->isActivo()) {
                continue; // Saltar usuarios inactivos
            }

            $distance = $this->calculateDistance(
                $currentUser->getLatitud(),
                $currentUser->getLongitud(),
                $user->getLatitud(),
                $user->getLongitud()
            );

            if ($distance <= $radiusKm) {
                $nearbyUsers[] = [
                    'id' => $user->getId(),
                    'nombre' => $user->getNombre(),
                    'distanciaKm' => round($distance, 2)
                ];
            }
        }

        // Ordenar por distancia
        usort($nearbyUsers, function($a, $b) {
            return $a['distanciaKm'] <=> $b['distanciaKm'];
        });

        return $nearbyUsers;
    }

    // Método auxiliar para calcular distancia entre dos coordenadas (Haversine)
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Método auxiliar para obtener invitaciones del usuario
    private function getUserInvitations(User $user, EntityManagerInterface $em): array
    {
        $invitaciones = $em->getRepository(Invitaciones::class)->findBy(['IdUsuarioReceptor' => $user]);
        
        $result = [];
        foreach ($invitaciones as $inv) {
            $result[] = [
                'id' => $inv->getId(),
                'usuarioOrigen' => $inv->getIdUsuarioRemitente()->getNombre(),
                'chatId' => $inv->getChatId()->getId(),
                'mensaje' => $inv->getMensaje(),
                'fecha' => $inv->getFechaInvitacion()->format('Y-m-d\TH:i:s')
            ];
            
            // Eliminar la invitación después de recuperarla
            $em->remove($inv);
        }
        
        // Guardar los cambios (eliminar las invitaciones)
        if (!empty($result)) {
            $em->flush();
        }
        
        return $result;
    }

    // Método auxiliar para obtener mensajes nuevos de un chat
    private function getNewMessages(int $chatId, int $lastMessageId, EntityManagerInterface $em): array
    {
        $chat = $em->getRepository(Chats::class)->find($chatId);
        if (!$chat) {
            return [];
        }

        $qb = $em->getRepository(Mensajes::class)->createQueryBuilder('m');
        $mensajes = $qb->where('m.chatPerteneciente = :chat')
            ->andWhere('m.id > :lastId')
            ->setParameter('chat', $chat)
            ->setParameter('lastId', $lastMessageId)
            ->orderBy('m.fechaHora', 'ASC')
            ->getQuery()
            ->getResult();
        
        $result = [];
        foreach ($mensajes as $msg) {
            $result[] = [
                'id' => $msg->getId(),
                'contenido' => $msg->getContenido(),
                'nombreUsuario' => $msg->getNombreUsuario()->getNombre(),
                'fecha' => $msg->getFechaHora()->format('Y-m-d\TH:i:s')
            ];
        }
        
        return $result;
    }

    // Método auxiliar para obtener usuarios de un chat
    private function getChatUsers(int $chatId, EntityManagerInterface $em): array
    {
        $chat = $em->getRepository(Chats::class)->find($chatId);
        if (!$chat) {
            return [];
        }
        
        $result = [];
        foreach ($chat->getUsers() as $usuario) {
            $result[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre()
            ];
        }
        
        return $result;
    }

    /*ENDPOINT API INVITAR* */
    #[Route('/api/invitacion', name: 'app_api_invitacion', methods: ['POST'])]
    public function invitar(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario', 'tokenUsuarioInvitado', 'idChat', 'mensaje']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['tokenUsuario', 'tokenUsuarioInvitado', 'idChat', 'mensaje']]
                ], 400);
            }

            $tokenUsuario = isset($data['tokenUsuario']) ? trim($data['tokenUsuario']) : null;
            $tokenUsuarioInvitado = isset($data['tokenUsuarioInvitado']) ? trim($data['tokenUsuarioInvitado']) : null;
            $idChat = isset($data['idChat']) ? $data['idChat'] : null;
            $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : null;

            // Validar campos obligatorios
            $missingFields = [];
            if (empty($tokenUsuario)) $missingFields[] = 'tokenUsuario';
            if (empty($tokenUsuarioInvitado)) $missingFields[] = 'tokenUsuarioInvitado';
            if (empty($idChat)) $missingFields[] = 'idChat';
            if (empty($mensaje)) $missingFields[] = 'mensaje';

            if (!empty($missingFields)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => $missingFields]
                ], 400);
            }

            // Validar formato del token del usuario emisor
            if (!preg_match('/^[a-f0-9]{64}$/i', $tokenUsuario)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token del usuario inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Validar formato del token del usuario invitado
            if (!preg_match('/^[a-f0-9]{64}$/i', $tokenUsuarioInvitado)) {
                return $this->json([
                    'success' => false,
                    'message' => 'El usuario invitado no existe o no está activo',
                    'error' => ['tokenUsuarioInvitado' => 'No válido']
                ], 404);
            }

            // Validar longitud del mensaje
            if (strlen($mensaje) > 255) {
                return $this->json([
                    'success' => false,
                    'message' => 'El mensaje excede la longitud máxima',
                    'error' => ['mensaje' => 'Máximo 255 caracteres']
                ], 400);
            }

            // Buscar usuario emisor
            $userEmisor = $userRepo->findOneBy(['token' => $tokenUsuario]);
            if (!$userEmisor instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token del usuario inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Buscar usuario invitado
            $userInvitado = $userRepo->findOneBy(['token' => $tokenUsuarioInvitado]);
            if (!$userInvitado instanceof User || !$userInvitado->isActivo()) {
                return $this->json([
                    'success' => false,
                    'message' => 'El usuario invitado no existe o no está activo',
                    'error' => ['tokenUsuarioInvitado' => 'No válido']
                ], 404);
            }

            // Validar que el chat existe
            $chat = $em->getRepository(Chats::class)->find($idChat);
            if (!$chat) {
                return $this->json([
                    'success' => false,
                    'message' => 'El chat no existe',
                    'error' => ['idChat' => 'No válido']
                ], 404);
            }

            // Crear la invitación
            $invitacion = new Invitaciones();
            $invitacion->setIdUsuarioRemitente($userEmisor);
            $invitacion->setIdUsuarioReceptor($userInvitado);
            $invitacion->setChatId($chat);
            $invitacion->setMensaje($mensaje);
            $invitacion->setFechaInvitacion(new DateTime());
            $em->persist($invitacion);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Invitación creada',
                'data' => [
                    'tokenUsuario' => $tokenUsuario,
                    'tokenUsuarioInvitado' => $tokenUsuarioInvitado,
                    'idChat' => $idChat
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API MENSAJE* */
    #[Route('/api/mensaje', name: 'app_api_mensaje', methods: ['POST'])]
    public function mensaje(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id', 'Contenido', 'tokenUsuario']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id', 'Contenido', 'tokenUsuario']]
                ], 400);
            }

            $chatId = isset($data['chat_id']) ? (int)$data['chat_id'] : null;
            $contenido = isset($data['Contenido']) ? trim($data['Contenido']) : null;
            $imagen = isset($data['Imagen']) ? $data['Imagen'] : null;
            $tokenUsuario = isset($data['tokenUsuario']) ? trim($data['tokenUsuario']) : null;

            // Validar campos obligatorios
            $missingFields = [];
            if ($chatId === null || $chatId === 0) $missingFields[] = 'chat_id';
            if (empty($contenido)) $missingFields[] = 'Contenido';
            if (empty($tokenUsuario)) $missingFields[] = 'tokenUsuario';

            if (!empty($missingFields)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => $missingFields]
                ], 400);
            }

            // Validar formato del token
            if (!preg_match('/^[a-f0-9]{64}$/i', $tokenUsuario)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token del usuario inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Validar longitud del contenido
            if (strlen($contenido) > 255) {
                return $this->json([
                    'success' => false,
                    'message' => 'El mensaje excede la longitud máxima permitida',
                    'error' => ['mensaje' => 'Máximo 255 caracteres']
                ], 400);
            }

            // Buscar usuario
            $user = $userRepo->findOneBy(['token' => $tokenUsuario]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token del usuario inválido',
                    'error' => (object)[]
                ], 401);
            }

            // Validar que el chat existe
            $chat = $em->getRepository(Chats::class)->find($chatId);
            if (!$chat) {
                return $this->json([
                    'success' => false,
                    'message' => 'El chat no existe',
                    'error' => ['chat_id' => 'No válido']
                ], 404);
            }

            // Validar que el usuario pertenece al chat
            if (!$chat->getUsers()->contains($user)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No tienes permisos para enviar mensajes en este chat',
                    'error' => (object)[]
                ], 403);
            }

            // Validar tamaño de imagen si se proporciona (máximo 12 MB)
            if ($imagen) {
                $imagenSize = strlen(base64_decode($imagen));
                if ($imagenSize > 12 * 1024 * 1024) { // 12 MB
                    return $this->json([
                        'success' => false,
                        'message' => 'La imagen excede el tamaño máximo permitido',
                        'error' => ['Imagen' => 'Máximo 12 MB']
                    ], 400);
                }
            }

            // Crear y guardar el mensaje
            $mensaje = new Mensajes();
            $mensaje->setChatPerteneciente($chat);
            $mensaje->setNombreUsuario($user);
            $mensaje->setContenido($contenido);
            $mensaje->setImagen($imagen);
            $mensaje->setFechaHora(new DateTime());
            $em->persist($mensaje);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'mensaje_id' => $mensaje->getId(),
                    'fecha' => $mensaje->getFechaHora()->format('Y-m-d\TH:i:s'),
                    'tokenUsuario' => $tokenUsuario
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al enviar mensaje',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API SALIR CHAT* */
    #[Route('/api/chat/salir', name: 'app_api_salir_chat', methods: ['POST'])]
    public function salirChat(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id']]
                ], 400);
            }

            // Obtener el token del header Authorization o del body
            $token = null;
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                if (isset($data['tokenUsuario'])) {
                    $token = trim($data['tokenUsuario']);
                }
            }

            $chatId = isset($data['chat_id']) ? (int)$data['chat_id'] : null;

            // Validar campos obligatorios
            if ($chatId === null || $chatId === 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id']]
                ], 400);
            }

            // Validar token
            if (empty($token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            // Buscar usuario
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            // Validar que el chat existe
            $chat = $em->getRepository(Chats::class)->find($chatId);
            if (!$chat) {
                return $this->json([
                    'success' => false,
                    'message' => 'El chat no existe',
                    'error' => ['chat_id' => 'No válido']
                ], 404);
            }

            // Validar que no se puede salir del chat general
            if ($chat->isEsGeneral()) {
                return $this->json([
                    'success' => false,
                    'message' => 'No puedes salir del chat general',
                    'error' => (object)[]
                ], 403);
            }

            // Validar que el usuario pertenece al chat
            if (!$chat->getUsers()->contains($user)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No puedes salir de un chat al que no perteneces',
                    'error' => (object)[]
                ], 403);
            }

            // Eliminar al usuario del chat y eliminar sus mensajes
            $chat->removeUser($user);
            $mensajesRepo = $em->getRepository(Mensajes::class);
            $mensajes = $mensajesRepo->findBy(['chatPerteneciente' => $chat, 'nombreUsuario' => $user]);
            foreach ($mensajes as $mensaje) {
                $em->remove($mensaje);
            }
            $em->persist($chat);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Has salido del chat',
                'data' => (object)[]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error',
                'error' => (object)[]
            ], 500);
        }
    }

    /*ENDPOINT API CAMBIAR CHAT* */
    #[Route('/api/chat/cambiarchat', name: 'app_api_cambiar_chat', methods: ['POST'])]
    public function cambiarChat(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id_nuevo']]
                ], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id_nuevo']]
                ], 400);
            }

            // Obtener el token del header Authorization o del body
            $token = null;
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } else {
                if (isset($data['tokenUsuario'])) {
                    $token = trim($data['tokenUsuario']);
                }
            }

            $chatIdNuevo = isset($data['chat_id_nuevo']) ? (int)$data['chat_id_nuevo'] : null;

            // Validar campos obligatorios
            if ($chatIdNuevo === null || $chatIdNuevo === 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                    'error' => ['fields' => ['chat_id_nuevo']]
                ], 400);
            }

            // Validar token
            if (empty($token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            // Buscar usuario
            $user = $userRepo->findOneBy(['token' => $token]);
            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                    'error' => (object)[]
                ], 401);
            }

            // Validar que el usuario está en algún chat actualmente
            $chatsActuales = $user->getChatPerteneciente();
            if ($chatsActuales->isEmpty()) {
                return $this->json([
                    'success' => false,
                    'message' => 'No puedes cambiar de chat porque no estás en ningún chat activo',
                    'error' => (object)[]
                ], 403);
            }

            // Validar que el chat destino existe
            $chatNuevo = $em->getRepository(Chats::class)->find($chatIdNuevo);
            if (!$chatNuevo) {
                return $this->json([
                    'success' => false,
                    'message' => 'El chat al que intentas cambiar no existe',
                    'error' => ['chat_id_nuevo' => 'No válido']
                ], 404);
            }

            // Validar que el usuario pertenece al chat de destino
            if (!$chatNuevo->getUsers()->contains($user)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No perteneces a este chat',
                    'error' => (object)[]
                ], 403);
            }
            // $user->setChatActivo($chatNuevo);
            // $em->persist($user);
            // $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Has cambiado de chat',
                'data' => (object)[]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al cambiar de chat',
                'error' => (object)[]
            ], 500);
        }
    }



}
