## Complete setup walkthrough

### Step 1 — Prerequisites
Install these if you haven't already:
- **Docker Desktop** → [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop)
- Make sure Docker Desktop is **running** before continuing (look for the whale icon in your taskbar)

---

### Step 2 — Drop in the fixed files

Copy the 4 files above into your project, replacing what's there:

```
automated-requesting-system/
├── .env.docker                     
└── docker/
    ├── initdb/
    │   └── 01_init.sql               
    └── apache/
        ├── 000-default.conf         
        └── default-ssl.conf           
```

---

### Step 3 — Open a terminal in your project root

```bash
cd path/to/automated-requesting-system
```

---

### Step 4 — First run

```bash
docker compose up --build
```

This does everything automatically — builds the PHP/Apache image, installs Composer deps, starts MariaDB, waits for it to be healthy, then seeds all your tables from `01_init.sql`. First run takes about **2–3 minutes**.

You'll know it's ready when you see:
```
ars_app  | AH00558: apache2: ... httpd started
```

---

### Step 5 — Open the app

| What | URL |
|---|---|
| **App** | `https://localhost/automated-requesting-system/public` |
| **phpMyAdmin** | `http://localhost:8080` |

Your browser will show a **certificate warning** — click **Advanced → Proceed to localhost**. This is expected because the cert is self-signed for local testing.

---

### Useful commands after first run

```bash
# Start without rebuilding (normal day-to-day)
docker compose up

# Stop containers (keeps your data)
docker compose down

# View live logs
docker compose logs -f app

# Full reset — wipes DB and starts fresh
docker compose down -v
docker compose up --build

# Open a shell inside the app container
docker exec -it ars_app bash

# Run a command inside the DB container
docker exec -it ars_db mariadb -u root -proot arsdb
```