SELECT p.id, p.created_at, p.updated_at, p.gets, p.size, p.file_name, n.name, b.nick, p.number, f.created_at as first_appearance
FROM mcol.packets p 
JOIN mcol.networks n on p.network_id = n.id
JOIN mcol.bots b on p.bot_id = b.id
JOIN mcol.`channels` c on p.channel_id = c.id
INNER JOIN mcol.file_first_appearances f on p.file_name = f.file_name
WHERE 1
AND b.nick NOT LIKE 'Beast-%'
-- AND b.nick <> '561AARU6K'
AND b.nick <> ''
AND p.file_name NOT LIKE '%KORSUB%'
AND p.file_name NOT LIKE '%Korean%'
AND p.file_name NOT LIKE '%german%'
AND p.file_name NOT LIKE '%CHINESE%'
AND p.file_name NOT LIKE '%FRENCH%'
AND p.file_name NOT LIKE '%.DoVi%'
AND p.file_name NOT LIKE '%.DV.%'
-- AND p.file_name LIKE '%MP3%'
AND (p.file_name LIKE '%1080%' OR p.file_name LIKE '%2160%')
-- AND p.file_name LIKE '%2160%'
-- AND p.file_name LIKE '%x265%'
-- AND p.file_name LIKE '%2024%'
-- AND p.file_name LIKE '%AMZN%'
AND f.created_at > '2024-09-11 23:59:59'
-- AND f.created_at > '2024-05-30 23:59:59'
-- AND p.file_name RLIKE '[.]+S[0-9]+[.]+'
-- AND p.file_name LIKE '%Halt.and.Catch.Fire.S01%'
-- AND p.file_name LIKE '%Breaking.Bad%'
-- AND p.file_name LIKE '%Raising.Dion.S02E03%'
-- AND p.file_name LIKE '%Slow.Horses.S04%'
-- AND p.file_name LIKE '%The.Lord.of.the.Rings.The.Rings.of.Power.S02E03.repack%'
-- AND b.nick = '[MG]-4k-Movies|Archive'
-- AND (b.nick = '[MG]-4k-Movies|POS' OR b.nick = '[MG]-4k-Movies|Archive')
-- AND b.nick = '[MG]-Request|Bot|Gelato'
AND p.file_name RLIKE '.*tar$|.*mkv$|.*avi$|.*mp4$|.*nsp$|.*dmg$|.*iso$|.*pdf$|.*pkg$|.*epub$|.*m4a$|.*m4v$|.*exe$|.*dll$|.*rar$|.*mobi$|.*m4b$|.*txt$|.*mp3$|.*wav$'
ORDER BY p.created_at DESC;
-- ORDER BY f.created_at DESC;
-- ORDER BY p.updated_at DESC;
-- ORDER BY p.gets DESC;
-- ORDER BY p.file_name;