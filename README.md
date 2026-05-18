# Nexus POS App

# 🐳 MySQL Docker Setup Guide


▶️ Start MySQL (and other services)

docker compose up -d


🛑 Stop and Remove Containers

docker compose down


🔁 Restart Only MySQL
docker compose restart mysql


📜 View Logs (Live)
docker compose logs -f mysql


💻 Access MySQL Shell

docker exec -it mysql mysql -u root -p

⏸ Stop Without Removing Containers
docker compose stop

▶️ Start Previously Stopped Containers
docker compose start

🧹 Remove Everything (Including Data)

⚠️ Warning: This deletes all MySQL data permanently.

docker compose down -v

🪣 Backup Database
docker exec -i mysql mysqldump -u root -psecret appdb > backup.sql

♻️ Restore Database
cat backup.sql | docker exec -i mysql mysql -u root -psecret appdb

🧭 Daily Workflow Example
cd path/to/your/project
docker compose up -d         # Start MySQL + phpMyAdmin
# work as usual...
docker compose logs -f mysql  # (Optional) check logs
docker compose down           # Stop when done

🌐 Access phpMyAdmin

Once running, open your browser:

http://localhost:8080


Login credentials:

Server: mysql

Username: app

Password: app_pass