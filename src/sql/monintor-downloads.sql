SELECT * FROM mcol.downloads WHERE STATUS <> 'COMPLETED' order by created_at DESC;

DELETE FROM mcol.downloads WHERE status <> 'COMPLETED';