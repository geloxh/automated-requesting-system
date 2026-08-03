This folder holds the real TLS cert/key that gets mounted over the self-signed
one baked into the Docker image — see the "Before you build: the certificate
requirement" section in `../../mobile-app/CAPACITOR.md` and `../../TAILSCALE.md`.

**Never commit `.crt`/`.key` files here** — they're already excluded in `.gitignore`.
This README is the only thing meant to be tracked in git.

Generate them with (run on the Docker host, once it's joined your tailnet and
HTTPS Certificates is enabled in the Tailscale admin console):

```
tailscale cert --cert-file=ars-selfsigned.crt --key-file=ars-selfsigned.key ars-server.your-tailnet.ts.net
```

Run that command from inside this folder (or move the two output files here
afterward) so the filenames match exactly what `docker-compose.yml` expects.
Then uncomment the two cert lines under the `app` service's `volumes:` in
`../../docker-compose.yml` and run:

```
docker compose up -d --force-recreate app
```

No image rebuild needed — Apache picks up the new files on container start.

**Renewal:** Tailscale-issued certs are Let's Encrypt-backed and typically
valid ~90 days. Re-run the `tailscale cert` command above before it expires
(same filenames — the mounted files just get overwritten), then:

```
docker compose restart app
```

Consider a scheduled task (Windows Task Scheduler) to run both commands
periodically so this doesn't require a manual reminder.