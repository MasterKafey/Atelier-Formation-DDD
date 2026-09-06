<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Throwable;

/**
 * Le coeur dit CE QUI s'est mal passe. L'adaptateur decide COMMENT le dire.
 *
 * Un service applicatif doit rester utilisable depuis le web, une API, une CLI ou un
 * test. Il ne peut donc pas rendre une « erreur de formulaire » : le formulaire est une
 * notion web. La convention orientee objet est de lever une exception — encore faut-il
 * distinguer celles qui sont destinees a l'utilisateur des autres.
 *
 * C'est tout le role de cette interface : elle marque les exceptions dont le message a
 * vocation a etre montre, et elle transporte de quoi le formuler dans n'importe quelle
 * langue et n'importe quel media. Elle ne transporte PAS le texte : traduire est un
 * travail d'infrastructure.
 *
 * Toutes les autres exceptions du domaine restent invisibles a l'utilisateur : elles
 * signalent soit un abus, soit un defaut de l'interface, et se traitent dans les logs.
 */
interface UserErrorMessage extends Throwable
{
    public function translationKey(): string;

    /** @return array<string, string> */
    public function translationParameters(): array;
}
