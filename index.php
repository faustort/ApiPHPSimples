<?php
require_once __DIR__ . '/vendor/autoload.php';

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;

// Define the "Livro" type
$livroType = new ObjectType([
    'name' => 'Livro',
    'fields' => [
        'codLivro' => [
            'type' => Type::int(),
            'description' => 'The ID of the livro'
        ],
        'idEmail' => [
            'type' => Type::string(),
            'description' => 'The email of the usuario who owns the livro'
        ],
        'nomeLivro' => [
            'type' => Type::string(),
            'description' => 'The title of the livro'
        ],
        'capaLivro' => [
            'type' => Type::string(),
            'description' => 'The cover of the livro'
        ]
    ]
]);

// Define the "Usuario" type
$usuarioType = new ObjectType([
    'name' => 'Usuario',
    'fields' => [
        'idEmail' => [
            'type' => Type::string(),
            'description' => 'The email of the usuario'
        ],
        'nome' => [
            'type' => Type::string(),
            'description' => 'The name of the usuario'
        ]
    ]
]);

// Define the "Query" type
$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'livros' => [
            'type' => Type::listOf($livroType),
            'description' => 'List of all livros owned by a usuario',
            'args' => [
                'usuarioEmail' => [
                    'type' => Type::string(),
                    'description' => 'Email of the usuario who owns the livros'
                ]
            ],
            'resolve' => function ($root, $args) {
                $servername = " ";
                $username = " ";
                $password = " ";
                $dbname = " ";

                // Create connection
                $conn = new mysqli($servername, $username, $password, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Get the usuario's ID based on their email
                $usuarioEmail = $args['usuarioEmail'];
                $usuarioIdQuery = "SELECT * FROM livros,usuario WHERE livros.idEmail LIKE '{$usuarioEmail}' AND usuario.idEmail LIKE '{$usuarioEmail}";
                $usuarioIdResult = $conn->query($usuarioIdQuery);
                $usuarioId = null;
                if ($usuarioIdResult->num_rows > 0) {
                    $usuarioId = $usuarioIdResult->fetch_assoc()['idUsuario'];
                }

                if (!$usuarioId) {
                    return null;
                }

                // Get all livros owned by the usuario with the given email
                $livrosQuery = "SELECT * FROM livros WHERE idEmail LIKE '{$usuarioEmail}'";
                $livrosResult = $conn->query($livrosQuery);
                $livros = [];
                if ($livrosResult->num_rows > 0) {
                    while ($row = $livrosResult->fetch_assoc()) {
                        $livros[] = $row;
                    }
                }

                $conn->close();

                return $livros;
            }
        ]
    ]
]);

// Define the schema with the "Query" type
// Define the schema with the "Query" type
$schema = new Schema([
    'query' => $queryType
]);

// Get the GraphQL query string from the request
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '', true);

// Execute the GraphQL query and return the result
$query = isset($input['query']) ? $input['query'] : null;
$result = GraphQL::executeQuery($schema, $query);
$output = $result->toArray();
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);
