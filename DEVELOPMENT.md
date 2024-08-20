#1. Set DISTRIBUTED_MODE=true.
Otherwise, Quota / Rate tracking will not work (use local file, rather than Cache)

#2. Set CACHE_DRIVER="redis"
Otherwise, certain features will not work properly:
- Quota / Rate tracking
- Campaign debug
- Campaign check and force rerun (stuck 'sending')
- Campaign delay flag
- More...

#3. Same database and Redis connections
Make sure both Worker and Master instances share the same connections to
- Database
- Redis

#4. Same prefixes
Make sure both Worker and Master instances use the same value for:
- DATABASE_TABLE_PREFIX
- REDIS_PREFIX
- CACHE_PREFIX

#5. Same APP_KEY

#6. Same APP_URL
Otherwise, generated link may become http://woker.localhost/...

#6. Make sure the Worker instance does not execute jobs of 'default' queue
Also, make sure there is at least 1 queue monitor for 'default' jobs on the Master instance.

Otherwise, certain features will not work. For example: subscribers import.
It is because the Worker instance does not have access to the uploaded file
which is uploaded to the Master app. (import is a 'default' job)

#7. Practices
Comment out the "queue:work" line in Console/Kernel.php of the master instance, use Supervisor instead

##7.1. Example of Master instance supervisor config

[program:app-master]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php -q /home/master/app/artisan queue:work --queue=default --tries=1 --max-time=180
autostart=true
autorestart=true
user=sendmails
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/output
stderr_logfile=/var/log/supervisor/error

##7.2. Example of Worker instance supervisor config

[program:app-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php -q /home/worker/app/artisan queue:work --queue=batch --tries=1 --max-time=180
autostart=true
autorestart=true
user=sendmails
numprocs=15
redirect_stderr=true
stdout_logfile=/var/log/supervisor/output
stderr_logfile=/var/log/supervisor/error
