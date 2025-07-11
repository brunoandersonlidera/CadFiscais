<?php
// Lista de DDI (códigos de discagem internacional) dos principais países
$ddi_list = [
    '+55' => 'Brasil',
    '+1' => 'Estados Unidos/Canadá',
    '+33' => 'França',
    '+44' => 'Reino Unido',
    '+49' => 'Alemanha',
    '+34' => 'Espanha',
    '+39' => 'Itália',
    '+31' => 'Países Baixos',
    '+32' => 'Bélgica',
    '+46' => 'Suécia',
    '+47' => 'Noruega',
    '+45' => 'Dinamarca',
    '+358' => 'Finlândia',
    '+351' => 'Portugal',
    '+41' => 'Suíça',
    '+43' => 'Áustria',
    '+48' => 'Polônia',
    '+420' => 'República Tcheca',
    '+36' => 'Hungria',
    '+40' => 'Romênia',
    '+30' => 'Grécia',
    '+90' => 'Turquia',
    '+7' => 'Rússia',
    '+380' => 'Ucrânia',
    '+375' => 'Bielorrússia',
    '+371' => 'Letônia',
    '+372' => 'Estônia',
    '+370' => 'Lituânia',
    '+81' => 'Japão',
    '+82' => 'Coreia do Sul',
    '+86' => 'China',
    '+91' => 'Índia',
    '+852' => 'Hong Kong',
    '+886' => 'Taiwan',
    '+65' => 'Singapura',
    '+60' => 'Malásia',
    '+66' => 'Tailândia',
    '+84' => 'Vietnã',
    '+62' => 'Indonésia',
    '+63' => 'Filipinas',
    '+61' => 'Austrália',
    '+64' => 'Nova Zelândia',
    '+27' => 'África do Sul',
    '+20' => 'Egito',
    '+234' => 'Nigéria',
    '+254' => 'Quênia',
    '+212' => 'Marrocos',
    '+216' => 'Tunísia',
    '+213' => 'Argélia',
    '+52' => 'México',
    '+54' => 'Argentina',
    '+56' => 'Chile',
    '+57' => 'Colômbia',
    '+58' => 'Venezuela',
    '+51' => 'Peru',
    '+593' => 'Equador',
    '+595' => 'Paraguai',
    '+598' => 'Uruguai',
    '+591' => 'Bolívia',
    '+507' => 'Panamá',
    '+506' => 'Costa Rica',
    '+502' => 'Guatemala',
    '+503' => 'El Salvador',
    '+504' => 'Honduras',
    '+505' => 'Nicarágua',
    '+501' => 'Belize',
    '+592' => 'Guiana',
    '+594' => 'Guiana Francesa',
    '+597' => 'Suriname',
    '+971' => 'Emirados Árabes Unidos',
    '+966' => 'Arábia Saudita',
    '+974' => 'Catar',
    '+973' => 'Bahrein',
    '+965' => 'Kuwait',
    '+968' => 'Omã',
    '+962' => 'Jordânia',
    '+961' => 'Líbano',
    '+963' => 'Síria',
    '+964' => 'Iraque',
    '+98' => 'Irã',
    '+93' => 'Afeganistão',
    '+92' => 'Paquistão',
    '+880' => 'Bangladesh',
    '+94' => 'Sri Lanka',
    '+95' => 'Myanmar',
    '+856' => 'Laos',
    '+855' => 'Camboja',
    '+977' => 'Nepal',
    '+975' => 'Butão',
    '+976' => 'Mongólia',
    '+850' => 'Coreia do Norte',
    '+967' => 'Iêmen',
    '+968' => 'Omã',
    '+973' => 'Bahrein',
    '+974' => 'Catar',
    '+975' => 'Butão',
    '+976' => 'Mongólia',
    '+977' => 'Nepal',
    '+978' => 'Abkhazia',
    '+979' => 'Artsakh',
    '+992' => 'Tajiquistão',
    '+993' => 'Turcomenistão',
    '+994' => 'Azerbaijão',
    '+995' => 'Geórgia',
    '+996' => 'Quirguistão',
    '+998' => 'Uzbequistão'
];

// Função para obter a lista de DDI
function getDDIList() {
    global $ddi_list;
    return $ddi_list;
}

// Função para validar DDI
function validateDDI($ddi) {
    global $ddi_list;
    return array_key_exists($ddi, $ddi_list);
}

// Função para obter o nome do país pelo DDI
function getCountryByDDI($ddi) {
    global $ddi_list;
    return isset($ddi_list[$ddi]) ? $ddi_list[$ddi] : 'Desconhecido';
}

// Função para validar número de celular brasileiro
function validateBrazilianPhone($phone) {
    // Remove todos os caracteres não numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Verifica se tem 10 ou 11 dígitos (com DDD)
    if (strlen($phone) < 10 || strlen($phone) > 11) {
        return false;
    }
    
    // Verifica se começa com 9 (celular) ou 6,7,8,9 (fixo)
    $ddd = substr($phone, 0, 2);
    $number = substr($phone, 2);
    
    // DDDs válidos no Brasil
    $valid_ddds = [
        11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 24, 27, 28, 31, 32, 33, 34, 35, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48, 49, 51, 53, 54, 55, 61, 62, 63, 64, 65, 66, 67, 68, 69, 71, 73, 74, 75, 77, 79, 81, 82, 83, 84, 85, 86, 87, 88, 89, 91, 92, 93, 94, 95, 96, 97, 98, 99
    ];
    
    if (!in_array($ddd, $valid_ddds)) {
        return false;
    }
    
    // Para celular, deve começar com 9
    if (strlen($number) == 9 && substr($number, 0, 1) != '9') {
        return false;
    }
    
    return true;
}

// Função para formatar número brasileiro
function formatBrazilianPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}
?>