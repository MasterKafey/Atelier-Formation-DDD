<?php

declare(strict_types=1);

namespace Bookshelf\Legacy;

/**
 * LE CODE DE DEPART.
 *
 * A lire, pas a modifier. C'est ce que fait Bookshelf aujourd'hui pour traiter une
 * commande. Ce fichier n'est volontairement pas autoloade ni teste : il sert de point
 * de comparaison pendant les trois journees.
 *
 * Les cinq problemes a reperer, dans l'ordre de gravite croissante :
 *
 *   1. Le scenario est illisible : le code ne dit jamais "enregistrer la commande",
 *      il montre comment on l'enregistre.
 *   2. La regle metier (le calcul du montant) est au milieu d'un traitement de formulaire.
 *   3. Le cas d'usage n'est appelable que depuis le web : pas d'API, pas de CLI, pas de cron.
 *   4. C'est intestable : il faut un serveur web, une base, une session et un serveur SMTP.
 *   5. Rien ne protege la coherence : email = 'foobar', quantity = -3, montant incoherent.
 */
final class OrderController
{
    public function orderEbookAction(Request $request): Response
    {
        $connection = $this->container->get('connection');

        $ebookPrice = $connection->execute(
            'SELECT price FROM ebooks WHERE id = :id',
            ['id' => $request->request->get('ebook_id')]
        )->fetchColumn(0);

        $orderAmount = (int) $request->get('quantity') * (int) $ebookPrice;

        $record = [
            'email'    => $request->get('email_address'),
            'quantity' => (int) $request->get('quantity'),
            'amount'   => $orderAmount,
        ];

        $columns = array_keys($record);
        $values  = array_map(
            fn ($value) => $connection->escape($value),
            array_values($record)
        );
        $sql = 'INSERT INTO orders (' . implode(', ', $columns)
             . ') VALUES (' . implode(', ', $values) . ')';

        $connection->execute($sql);

        $lastInsertedId = $connection->execute('SELECT LAST_INSERT_ID()')->fetchColumn(0);

        $this->container->get('session')->set('currentOrderId', $lastInsertedId);

        $message = (new Email())
            ->from($this->container->getParameter('system_email_address'))
            ->to($request->request->get('email_address'))
            ->html($this->container->get('twig')->render('email/order_confirmation.html.twig'));

        $this->container->get('mailer')->send($message);

        return new Response(/* ... */);
    }
}
