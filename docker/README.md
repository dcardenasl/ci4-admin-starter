# Docker — admin + API on a shared network

Both `ci4-admin-starter` and `ci4-api-starter` attach to an **external** bridge
network named `ci4-platform`. Run this once on the host before the first
`docker compose up`:

```bash
docker network create ci4-platform
```

Then bring up each stack in its own directory:

```bash
# Terminal 1 — API hub (port 8080)
cd ../ci4-api-starter
cp .env.docker.example .env.docker   # adjust DB credentials
docker compose up -d

# Terminal 2 — Admin (port 8082)
cd ../ci4-admin-starter
cp .env.docker.example .env          # uses ci4-api-app:80 as API target
docker compose up -d
```

Smoke test from inside the admin container:

```bash
docker compose exec app curl -s http://ci4-api-app:80/health
# expected: {"status":"healthy", ...}
```

The admin reaches the API via the container hostname `ci4-api-app` thanks to
the shared `ci4-platform` network. Browser URLs stay on `localhost:8080`
(API) and `localhost:8082` (admin) because each stack publishes its own
external port.

## Optional: ci4-domain-starter

When running a domain backend in parallel (e.g. SubscriptionKit):

```bash
cd ../ci4-domain-starter
docker compose up -d        # attach to ci4-platform too
```

Then set `domainApiClient.baseUrl = 'http://ci4-domain-app:80'` in the admin's
`.env`, scaffold the modules with `--service=domain`, and the admin will route
to both backends transparently.
