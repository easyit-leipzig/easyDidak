DELIMITER $$
--
-- Prozeduren
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `updateGruppen` ()   BEGIN
    UPDATE ue_gruppen
    SET 
        day_number = (DAYOFWEEK(CURDATE()) + 5) % 7 + 2,  /* Montag=1, Sonntag=7 */
        uhrzeit_ende = CURTIME()
    WHERE id = 10;
END$$

DELIMITER ;
