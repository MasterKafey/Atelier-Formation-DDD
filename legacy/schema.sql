-- L'existant en base. A lire avant l'atelier 2.
--
-- Notez `price INT` : le prix est en centimes. C'est une decision technique invisible
-- dans le code appelant, et personne ne l'a ecrite nulle part. Le jour ou quelqu'un
-- migrera vers DECIMAL, (int) '1.50' vaudra 1 et Bookshelf vendra des livres a un centime.

CREATE TABLE ebooks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    price       INT NOT NULL,          -- en centimes
    is_hidden   TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ebook_id    INT NOT NULL,
    email       VARCHAR(255) NOT NULL,
    quantity    INT NOT NULL,
    amount      INT NOT NULL,          -- en centimes
    vat_rate    INT NULL,              -- nullable : que signifie une commande sans TVA ?
    status      VARCHAR(20) NOT NULL DEFAULT 'pending'
);
