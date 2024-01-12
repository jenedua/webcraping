<?php

function extract_text($obj = null)
{
    $result = '';

    if ($obj instanceof DOMNodeList) {
        foreach ($obj as $node) {
            $result .= $node->textContent . "\n";
        }
    } elseif ($obj instanceof DOMNode) {
        $result = $obj->textContent;
    }

    return $result;
}

function getLinks($dom, $filter = "/lote/")
{
    $links = array();

    $anchorTags = $dom->getElementsByTagName('a');
    foreach ($anchorTags as $anchor) {
        $href = $anchor->getAttribute('href');
        if (!empty($href) && strpos($href, $filter) !== false) {
            $links[] = $href;
        }
    }

    return $links;
}

function extractData($xp, $query)
{
    $data = [];

    $els = $xp->query($query);

    if ($els) {
        foreach ($els as $el) {
            $data[] = extract_text($el);
        }
    }

    return $data;
}

$content = file_get_contents('https://amleiloeiro.com.br/');
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($content);
libxml_clear_errors();

$xp = new DOMXPath($dom);

// Obter todos os links
$allLinks = getLinks($dom);

// Extrair dados de texto
$query = "//*[@class='flex-1 text-center']";
$data = extractData($xp, $query);

// Adicionar um cabeçalho ao CSV
array_unshift($data, 'Text');

// Criar um arquivo CSV temporário
$tempCsvFile = fopen('php://temp', 'w+');
fputcsv($tempCsvFile, [$data[0]]); // Escrever o cabeçalho
unset($data[0]); // Remover o cabeçalho do array de dados

foreach ($data as $row) {
    fputcsv($tempCsvFile, [$row]);
}

// Obter todos os links que contêm "/lote/"
$loteLinks = getLinks($dom, "/lote/");

// Adicionar os links "/lote/" ao CSV
array_unshift($loteLinks, 'Link (Lote)');
foreach ($loteLinks as $link) {
    fputcsv($tempCsvFile, [$link]);
}

// Configurar cabeçalhos HTTP para download
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="crawler.csv"');

// Enviar o conteúdo do arquivo CSV para o navegador
rewind($tempCsvFile);
fpassthru($tempCsvFile);

// Fechar o arquivo CSV temporário
fclose($tempCsvFile);

exit; // Finalizar a execução para evitar saída adicional no final do script

?>
