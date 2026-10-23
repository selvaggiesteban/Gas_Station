-- F3: se elimina el feature de batallas 1:1 entre gestores.
-- El ranking / competencia pasa a ser exclusivamente por puntos acumulados.
--
-- Nota sobre los CHECK de weekly_challenges.goal_type y point_logs.reason:
-- en SQLite alterar un CHECK obliga a recrear la tabla completa, lo que con
-- foreign_keys activas borraria en cascada las filas hijas (challenge_participations
-- y point_logs). Por eso los CHECK en base de datos quedan como superconjunto de
-- los valores que la app permite escribir: el codigo ya no genera 'battles_won'
-- ni 'battle_win'.

DROP TABLE IF EXISTS battles;
