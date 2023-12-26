SELECT * FROM mcol.downloads order by created_at DESC;

DELETE FROM mcol.downloads WHERE status = 'QUEUED';