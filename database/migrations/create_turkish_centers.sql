-- Create turkish_centers table for storing Turkish institutions/associations in Germany
CREATE TABLE IF NOT EXISTS `turkish_centers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_city` (`city`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample Turkish centers in major German cities
INSERT INTO `turkish_centers` (`name`, `city`, `address`, `zip_code`, `contact_person`, `phone`, `email`, `is_active`) VALUES
-- Berlin
('Türkisch-Deutsches Zentrum Berlin', 'Berlin', 'Potsdamer Str. 96', '10785', 'Ahmet Yılmaz', '+49 30 12345678', 'info@tdz-berlin.de', 1),
('Türkische Gemeinde zu Berlin', 'Berlin', 'Oranienstraße 53', '10969', 'Fatma Öztürk', '+49 30 23456789', 'kontakt@tgb.de', 1),

-- Hamburg
('Türkische Gemeinde Hamburg', 'Hamburg', 'Hospitalstraße 111', '20359', 'Mehmet Demir', '+49 40 34567890', 'info@tgh.de', 1),
('Eimsbütteler Türkischer Elternverein', 'Hamburg', 'Osterstraße 86', '20259', 'Ayşe Schmidt', '+49 40 45678901', 'kontakt@etev.de', 1),

-- München
('Türkisch-Islamisches Kulturzentrum München', 'München', 'Gotzinger Straße 43', '81371', 'Ali Kaya', '+49 89 56789012', 'info@tikz-muenchen.de', 1),
('Türkische Elternvereinigung München', 'München', 'Goethestraße 53', '80336', 'Zeynep Weber', '+49 89 67890123', 'tev@muenchen.de', 1),

-- Köln
('Türkischer Elternverein Köln', 'Köln', 'Weidengasse 65', '50676', 'Hasan Çelik', '+49 221 78901234', 'info@tev-koeln.de', 1),
('Türkisch-Deutsche Bildungsstätte Köln', 'Köln', 'Eigelstein 135', '50668', 'Elif Müller', '+49 221 89012345', 'bildung@tdb-koeln.de', 1),

-- Frankfurt
('Türkische Gemeinde Frankfurt', 'Frankfurt', 'Konrad-Brosswitz-Straße 4', '60489', 'Mustafa Wagner', '+49 69 90123456', 'info@tgf.de', 1),
('Türkisches Kulturzentrum Frankfurt', 'Frankfurt', 'Hamburger Allee 2-10', '60486', 'Gülsüm Fischer', '+49 69 01234567', 'kultur@tkz-frankfurt.de', 1),

-- Stuttgart
('Türkische Gemeinde Baden-Württemberg', 'Stuttgart', 'Reinsburgstraße 82', '70178', 'İbrahim Schneider', '+49 711 23456780', 'info@tgbw.de', 1),
('Türkisches Bildungszentrum Stuttgart', 'Stuttgart', 'Nordbahnhofstraße 135', '70191', 'Sevim Becker', '+49 711 34567801', 'bildung@tbz-stuttgart.de', 1),

-- Düsseldorf
('Türkische Gemeinde Düsseldorf', 'Düsseldorf', 'Grafenberger Allee 186', '40237', 'Ömer Hoffmann', '+49 211 45678012', 'info@tgd.de', 1),
('Türkisches Kulturhaus Düsseldorf', 'Düsseldorf', 'Münsterstraße 156', '40476', 'Derya Koch', '+49 211 56780123', 'kultur@tkh-duesseldorf.de', 1),

-- Dortmund
('Türkisch-Islamisches Kulturzentrum Dortmund', 'Dortmund', 'Münsterstraße 97', '44145', 'Yusuf Lange', '+49 231 67801234', 'info@tikz-dortmund.de', 1),
('Türkische Elternvereinigung Dortmund', 'Dortmund', 'Osterlandwehr 12-14', '44145', 'Hatice Braun', '+49 231 78012345', 'tev@dortmund.de', 1),

-- Essen
('Türkische Gemeinde Essen', 'Essen', 'Viehofer Straße 8', '45127', 'Kemal Jung', '+49 201 89123456', 'info@tge.de', 1),

-- Leipzig
('Türkisch-Deutscher Kulturverein Leipzig', 'Leipzig', 'Eisenbahnstraße 66', '04315', 'Aylin Scholz', '+49 341 90234567', 'info@tdkv-leipzig.de', 1),

-- Bremen
('Türkische Gemeinde Bremen', 'Bremen', 'Rembertistraße 27', '28203', 'Serkan Richter', '+49 421 01345678', 'info@tgb-bremen.de', 1),

-- Hannover
('Türkisches Kulturzentrum Hannover', 'Hannover', 'Fössestraße 35', '30451', 'Deniz Klein', '+49 511 12456789', 'kultur@tkz-hannover.de', 1),

-- Nürnberg
('Türkische Gemeinde Nürnberg', 'Nürnberg', 'Hinteren Ledergasse 17', '90403', 'Emre Wolf', '+49 911 23567890', 'info@tgn.de', 1);
