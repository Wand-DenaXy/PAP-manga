<?php
// ════════════════════════════════════════════════════════
//  Controller — Suporte
//  Endpoint: assets/controller/controllerSuporte.php
//  Ações: criar, listar, detalhe, responder, faq
// ════════════════════════════════════════════════════════
require_once __DIR__ . '/../model/modelSuporte.php';

header('Content-Type: application/json; charset=utf-8');

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

switch ($acao) {
    case 'criar':
        $nome       = trim($_POST['nome'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $categoria  = $_POST['categoria'] ?? 'outro';
        $prioridade = $_POST['prioridade'] ?? 'media';
        $assunto    = trim($_POST['assunto'] ?? '');
        $mensagem   = trim($_POST['mensagem'] ?? '');

        if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
            jsonResponse(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Email inválido.'], 400);
        }

        $dados = [
            'utilizador_id' => isLoggedIn() ? $_SESSION['user_id'] : null,
            'nome'          => $nome,
            'email'         => $email,
            'categoria'     => $categoria,
            'prioridade'    => $prioridade,
            'assunto'       => $assunto,
            'mensagem'      => $mensagem,
        ];

        $result = ModelSuporte::criarTicket($dados);
        jsonResponse($result);
        break;

    case 'listar':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Não autenticado.'], 401);
        }
        $tickets = ModelSuporte::getTickets($_SESSION['user_id']);
        jsonResponse(['success' => true, 'tickets' => $tickets]);
        break;

    case 'detalhe':
        $ticketId = intval($_GET['id'] ?? 0);
        if ($ticketId <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
        }
        $ticket = ModelSuporte::getTicket($ticketId);
        $respostas = ModelSuporte::getRespostas($ticketId);
        jsonResponse(['success' => true, 'ticket' => $ticket, 'respostas' => $respostas]);
        break;

    case 'responder':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Não autenticado.'], 401);
        }
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $mensagem = trim($_POST['mensagem'] ?? '');

        if ($ticketId <= 0 || empty($mensagem)) {
            jsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 400);
        }

        $result = ModelSuporte::responder($ticketId, $_SESSION['user_id'], $mensagem);
        jsonResponse($result);
        break;

    case 'faq':
        $faq = ModelSuporte::getFAQ();
        jsonResponse(['success' => true, 'faq' => $faq]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida.'], 400);
}
