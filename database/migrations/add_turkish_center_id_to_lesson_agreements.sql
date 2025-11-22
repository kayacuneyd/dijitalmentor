-- Add turkish_center_id field to lesson_agreements table
ALTER TABLE `lesson_agreements`
ADD COLUMN `turkish_center_id` int(11) DEFAULT NULL AFTER `lesson_address`,
ADD KEY `fk_turkish_center` (`turkish_center_id`),
ADD CONSTRAINT `fk_lesson_agreement_turkish_center`
    FOREIGN KEY (`turkish_center_id`) REFERENCES `turkish_centers` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
