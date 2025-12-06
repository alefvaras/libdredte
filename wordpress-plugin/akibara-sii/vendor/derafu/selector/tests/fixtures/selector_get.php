<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [

    // Diccionario con datos para los casos de pruebas de lectura.
    'data' => [
        'zero' => 0,
        'k_simple' => 'v_simple',
        'k_nested1' => [
            'k_nested2' => 'v_nested',
        ],
        'k_nested1p' => [
            'k_nested2p' => [
                'k_nested3p' => 'v_nested',
            ],
        ],
        'array' => [1, 2, 3],
        'nested_array' => [
            'array' => [1, 2, 3],
        ],
        'mixed' => [
            [
                'key' => 10,
                'value' => 'hola',
            ],
            [
                'key' => 20,
                'value' => 'mundo',
            ],
            [
                'key' => 30,
                'value' => 'chao',
            ],
        ],
        'nested_mixed' => [
            'mixed' => [
                [
                    'key' => 10,
                    'value' => 'hola',
                ],
            ],
        ],
        'mixed_with_childs' => [
            [
                'key' => 20,
                'child' => [
                    'value' => 'hijo',
                ],
            ],
        ],
        'mixed_dash' => [
            [
                'key' => 'key-1',
                'value' => 'value-1',
            ],
        ],
        'selector_or' => [
            'k1' => null,
            'k2' => '',
            'k3' => 'v3',
            'k4' => 'v4',
        ],

        // Para pruebas unitarias más complejas.
        'complex_dict' => [
            'nested' => [
                'key1' => 'value1',
                'key2' => 2,
                'key3' => [1, 2, 3],
            ],
            'list_of_dicts' => [
                ['item_key' => 'item_value1'],
                ['item_key' => 'item_value2'],
            ],
        ],
        'bool_value' => true,
        'numeric_value' => 42,
        'empty_string' => '',
        'null_value' => null,
        'false_value' => false,
        'list_with_mixed_types' => ['text', 123, true, null],
    ],

    // Casos de prueba con los resultados esperados las lecturas del selector.
    'cases' => [

        //
        // Casos de selectores simples.
        //

        // Casos sin OR.
        'zero' => 0,
        'k_simple' => 'v_simple',
        'k_nested1.k_nested2' => 'v_nested',
        'k_nested1p.k_nested2p.k_nested3p' => 'v_nested',
        'array[1]' => 2,
        'nested_array.array' => [1, 2, 3],
        'nested_array.array[2]' => 3,
        'nested_array.array2[2]' => null, // Caso que no existe.
        'nested_array.array[0]' => 1,
        'mixed[key=20:value]' => 'mundo',
        'mixed2[key=20:value]' => null, // Caso que no existe.
        'mixed_dash[key=key-1:value]' => 'value-1',
        'nested_mixed.mixed[key=10:value]' => 'hola',
        'mixed_with_childs[key=20:child].value' => 'hijo',
        '(k_simple)' => 'v_simple',
        '"k_simple"' => 'k_simple',
        '(k_simple)(k_simple)' => 'v_simplev_simple',
        '"("(k_simple)")"' => '(v_simple)',
        '(k_simple)"k_simple"' => 'v_simplek_simple',
        '"k_simple"(k_simple)' => 'k_simplev_simple',
        '(k_simple)"k_simple""k_simple"' => 'v_simplek_simplek_simple',
        '"k_simple"(k_simple)(k_simple)' => 'k_simplev_simplev_simple',
        '"k_simple"(k_simple)"k_simple"' => 'k_simplev_simplek_simple',
        '(k_simple)"k_simple"(k_simple)' => 'v_simplek_simplev_simple',
        '"Forma de pago: "(k_nested1.k_nested2)' => 'Forma de pago: v_nested',
        '"Forma de pago: "(k_nested1.k_nested2)" / checkout_id: "(mixed_with_childs[key=20:child].value)' =>
            'Forma de pago: v_nested / checkout_id: hijo'
        ,

        // Casos con OR.
        'k_empty||k_simple' => 'v_simple',
        'k_empty||"cadena literal"' => 'cadena literal',
        '"val: "(k_simple)||" y "(k_nested1.k_nested2)' => 'val: v_simple',
        'selector_or.k1||selector_or.k2||selector_or.k3' => 'v3',
        '"Hola"||"Mundo"' => 'Hola',
        '"Hola"||k_simple' => 'Hola',
        'k_simple||"Hola"' => 'v_simple',
        '"Valor: "(k_simple||"Sin valor")' => 'Valor: v_simple',
        '"Valor: "(k_simple_bad||"Sin valor")' => 'Valor: Sin valor',
        '"Valor: "(k_simple||"Sin valor")||"Valor por defecto"' => 'Valor: v_simple',
        '(k_simple_bad||"")||"Valor por defecto"' => 'Valor por defecto',

        //
        // Casos simples con selector que incluye IF.
        //

        // Casos con IF ternario.
        '((k_simple) = "v_simple" ? (k_nested1.k_nested2) : (array[1]))' => 'v_nested',
        '((k_simple) != "no_v_simple" ? (k_nested1.k_nested2) : (array[1]))' => 'v_nested',
        '((array[2]) > "2" ? (nested_array.array[1]) : (nested_array.array[2]))' => 2,
        '((array[1]) < "2" ? (nested_array.array[0]) : (nested_array.array[2]))' => 3,
        '((mixed_with_childs[key=20:child].value) == "hijo" ? ("V") : ("F"))' => 'V',
        '((array) contains "2" ? ("Sí") : ("No"))' => 'Sí',
        '((k_simple) contains "simple" ? ("Sí") : ("No"))' => 'Sí',
        '((array) length "3" ? ("Sí") : ("No"))' => 'Sí',
        '((k_simple) length "8" ? ("Sí") : ("No"))' => 'Sí', // Longitud de 'v_simple' es 8.
        '((k_nested1.k_nested2) length "7" ? ("Sí") : ("No"))' => 'No', // Longitud de 'v_nested' es 8.

        // Casos con IF ternario y operador OR en selector.
        '((k_empty||"cadena literal") = "cadena literal" ? ("String") : ("Valor de k_empty"))' => 'String',

        //
        // Otras pruebas unitarias de casos con selectores simples.
        //

        // Pruebas con Diccionarios Anidados.
        'complex_dict.nested.key1' => 'value1',
        'complex_dict.nested.key2' => 2,
        'complex_dict.nested.key3[1]' => 2,

        // Pruebas con Listas de Diccionarios.
        'complex_dict.list_of_dicts[0].item_key' => 'item_value1',
        'complex_dict.list_of_dicts[1].item_key' => 'item_value2',

        // Pruebas de Error.
        'complex_dict.non_existent_key' => null, # Selector no válido.
        'complex_dict.' => null, # Selector vacío.
        'complex_dict..nested' => null, # Selector mal formado.
        'complex_dict.invalid..key' => null, # Selector doblemente mal formado.

        // Pruebas con Diversos Tipos de Datos.
        'numeric_value' => 42,
        'empty_string' => '',
        'bool_value' => true,
        'false_value' => false,
        'null_value' => null,

        // Casos donde se concatena con diversos tipos de datos (los de arriba).
        '"numeric_value: "(numeric_value)' => 'numeric_value: 42',
        '"empty_string: "(empty_string)' => 'empty_string: ',
        '"bool_value: "(bool_value)' => 'bool_value: true',
        '"false_value: "(false_value)' => 'false_value: false',
        '"null_value: "(null_value)' => 'null_value: null',

        // Casos con operador OR usando campos vacío.
        'null_value||null_value' => null,
        'empty_string||empty_string' => null, // Un OR entregará null si todos
                                              // los selectores son null o ''
                                              // (string vacío).
        'null_value||"NONE"' => 'NONE',
        'empty_string||"EMPTY"' => 'EMPTY',
        'false_value||"FALSE"' => false, // false no tiene valor por defecto,
                                         // ya que si tiene valor, es false.

        //  Casos con campos vacíos en Operadores ID.
        '((null_value) is "null" ? ("null_value es null") : ("null_value no es null"))' => 'null_value es null',

        // Casos de Borde con Operadores IF.
        '((numeric_value) > "41" ? ("Mayor a 41") : ("Menor o igual a 41"))' => 'Mayor a 41',
        '((bool_value) == "true" ? ("Verdadero") : ("Falso"))' => 'Verdadero',
        '((empty_string) == "" ? ("Cadena vacía") : ("Cadena no vacía"))' => 'Cadena vacía',

        // Prueba con lista mixta.
        'list_with_mixed_types[0]' => 'text',
        'list_with_mixed_types[1]' => 123,
        'list_with_mixed_types[2]' => true,
        'list_with_mixed_types[3]' => null,

        //
        // Casos con JSONPath.
        //

        // Acceso Directo a una Clave en Primer Nivel.
        "$.k_simple" => "v_simple",

        // Acceso a un Elemento Anidado.
        "$.k_nested1.k_nested2" => "v_nested",

        // Acceso a un Elemento de un Arreglo por Índice.
        "$.array[1]" => 2,

        // Acceso a un Elemento Anidado Dentro de un Arreglo.
        "$.nested_array.array[2]" => 3,

        // Filtrar Elementos de un Arreglo (por valor existente),
        "$.mixed[?(@.key == 20)].value" => "mundo",

        // Filtrar Elementos de un Arreglo (resultado vacío).
        "$.mixed[?(@.key == 999)]" => null,

        // Acceso a Todos los Elementos de un Arreglo.
        "$.array[*]" => [1, 2, 3],

        // Obtener Elementos Basados en una Condición Compleja.
        "$.mixed[?(@.key > 15)].value" => ["mundo", "chao"],

        // Obtener un Elemento Anidado en un Diccionario dentro de un Arreglo.
        "$.mixed_with_childs[?(@.key == 20)].child.value" => "hijo",

        // Caso adicional (acceso a un elemento booleano).
        "$.bool_value" => true,

        //
        // Probar casos con JSONPath mezclados con los selectores originales.
        //

        // Acceso Directo y Concatenación de Texto.
        '"k_simple: "($.k_simple)' => 'k_simple: v_simple',

        // Selección con OR y JSONPath.
        '($.k_simple_bad)||($.k_simple)' => 'v_simple',

        // Valor por Defecto con OR y JSONPath.
        '($.k_simple_bad)||"Valor por defecto"' => 'Valor por defecto',

        // Condición con IF Ternario y JSONPath.
        '((($.k_simple_bad)||($.k_simple)) == "v_simple" ? ("V") : ("F")))' => 'V',

        // Concatenación con Valor de un Arreglo.
        '"array[0]: "($.array[0])' => 'array[0]: 1',

        // Filtrado de Arreglo y Concatenación.
        '"Elementos > 1 en array: " + str($.array[?(@ > 1)])' =>
            'Elementos > 1 en array: [2, 3]'
        ,

        // Acceso a Elemento Anidado y Concatenación.
        '"nested_array.array[2]: "($.nested_array.array[2])' =>
            'nested_array.array[2]: 3'
        ,

        // Filtrado de Arreglo de Diccionarios y Concatenación.
        '"mixed[key=20:value]: "($.mixed[?(@.key == 20)].value)' =>
            'mixed[key=20:value]: mundo'
        ,

        // Concatenación de un valor extraído del arreglo con texto.
        '"Primer valor en mixed: "($.mixed[0].value)' =>
            'Primer valor en mixed: hola'
        ,

        // Acceder a un valor anidado y concatenar con un valor de un arreglo
        // usando JSONPath.
        '"Valor anidado y primer valor de array: "($.k_nested1.k_nested2)" y "($.array[0])' =>
            'Valor anidado y primer valor de array: v_nested y 1'
        ,

        //
        // Probar casos con JMESPath.
        //

        // Acceso Directo a una Clave en Primer Nivel.
        "jmespath:k_simple" => "v_simple",

        // Acceso a un Elemento Anidado.
        "jmespath:k_nested1.k_nested2" => "v_nested",

        // Acceso a un Elemento de un Arreglo por Índice.
        "jmespath:array[1]" => 2,

        // Acceso a un Elemento Anidado Dentro de un Arreglo.
        "jmespath:nested_array.array[2]" => 3,

        // Filtrar Elementos de un Arreglo (por valor existente).
        "jmespath:mixed[?key == `20`].value" => "mundo",

        // Filtrar Elementos de un Arreglo (resultado vacío).
        "jmespath:mixed[?key == `999`]" => null,

        // Acceso a Todos los Elementos de un Arreglo.
        "jmespath:array[*]" => [1, 2, 3],

        // Obtener Elementos Basados en una Condición Compleja.
        "jmespath:mixed[?key > `15`].value" => ["mundo", "chao"],

        // Obtener un Elemento Anidado en un Diccionario dentro de un Arreglo.
        "jmespath:mixed_with_childs[?key == `20`].child.value" => "hijo",

        // Caso adicional (acceso a un elemento booleano).
        "jmespath:bool_value" => true,

        //
        // Probar casos con JMESPath mezclados con los selectores originales.
        //

        // Acceso Directo y Concatenación de Texto.
        '"k_simple: "(jmespath:k_simple)' => 'k_simple: v_simple',

        // Selección con OR y JMESPath.
        'jmespath:k_simple_bad||jmespath:k_simple' => 'v_simple',

        // Valor por Defecto con OR y JMESPath.
        'jmespath:k_simple_bad||"Valor por defecto"' => 'Valor por defecto',

        // Condición con IF Ternario y JMESPath.
        '((jmespath:k_simple_bad||jmespath:k_simple) == "v_simple" ? ("V") : ("F")))' => 'V',

        // Concatenación con Valor de un Arreglo.
        '"array[0]: "(jmespath:array[0])' => 'array[0]: 1',

        // Filtrado de Arreglo y Concatenación.
        '"Elementos > 1 en array: "(jmespath:array[? @ > `1`])' =>
            'Elementos > 1 en array: [2, 3]'
        ,

        // Acceso a Elemento Anidado y Concatenación.
        '"nested_array.array[2]: "(jmespath:nested_array.array[2])' =>
            'nested_array.array[2]: 3'
        ,

        // Filtrado de Arreglo de Diccionarios y Concatenación.
        '"mixed[key=20:value]: "(jmespath:mixed[?key == `20`].value)' =>
            'mixed[key=20:value]: mundo'
        ,

        // Concatenación de un valor extraído del arreglo con texto.
        '"Primer valor en mixed: "(jmespath:mixed[0].value)' =>
            'Primer valor en mixed: hola'
        ,

        // Acceder a un valor anidado y concatenar con un valor de un arreglo
        // usando JMESPath
        '"Valor anidado y primer valor de array: "(jmespath:k_nested1.k_nested2)" y "(jmespath:array[0])' =>
            'Valor anidado y primer valor de array: v_nested y 1'
        ,

    ],

];
