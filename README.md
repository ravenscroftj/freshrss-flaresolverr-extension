# FreshRSS Flaresolverr Extension

[![Mastodon Follow](https://img.shields.io/mastodon/follow/000117012?domain=https%3A%2F%2Ffosstodon.org%2F&style=social)](https://fosstodon.org/@jamesravey) ![BSD Licensed](https://img.shields.io/github/license/ravenscroftj/freshrss-flaresolverr-extension) 

FreshRSS plugin that provides cloudflare puzzle solving via flaresolverr

## Why

Some popular publishing sites, including [substack](https://substack.com/) use [Cloudflare](https://www.cloudflare.com/) to provide content caching and DDoS protection. 

If cloudflare suspects that your machine is a bot, they throw up a [challenge](https://developers.cloudflare.com/fundamentals/get-started/concepts/cloudflare-challenges/) - this is normally just a page that requires your browser to run some javascript which filters out simple scrapers that don't evaluate scripts. This means that Freshrss sometimes fails to retrieve feeds protected by cloudflare and it isn't "smart" enough to pass these cloudflare filters on its own.

This FreshRSS extension uses [Flaresolverr](https://github.com/FlareSolverr/FlareSolverr/) to start a headless browser (essentially a full copy of chrome or firefox but without a UI to look at) to parse and resolve these challenges and send the contents of an RSS feed through to FreshRSS as normal.

I stumbled across [ryancom16's solution](https://github.com/FreshRSS/FreshRSS/issues/4323) and decided to have a go at hardening it into a proper FreshRSS Plugin

## Install

### Set up FlareSolverr
You will need an instance of FlareSolverr running somewhere that is accessible to your FreshRSS instance. If you are using Docker Compose to manage FreshRSS then you can add FlareSolverr to your compose file. An example setup is shown below:

```yaml
version: "2.1"
services:
  flaresolverr:     
    image: ghcr.io/flaresolverr/flaresolverr:latest
    restart: always
    environment:
      - LOG_LEVEL=info
    ports:
      - 8191:8191

  freshrss:
    image: lscr.io/linuxserver/freshrss:latest
    container_name: freshrss
    environment:
      - PUID=1000
      - PGID=1000
      - TZ=Europe/London
    volumes:
      - ./data:/config
    ports:
      - 8080:80
    restart: unless-stopped

```

### Install the Plugin

1. Copy this whole directory to your FreshRSS `extensions` directory. The easiest option is probably to clone this repo:

```bash
cd /path/to/freshrss/extensions
git clone https://github.com/ravenscroftj/freshrss-flaresolverr-extension.git
```

2. Paste in the URL of your FlareSolverr instance in the settings window

![screenshot of the settings window for the plugin](assets/flaresolverr.png)

3. Copy the feed URL in bold

### Configure your Feeds

Prepend any feeds protected by Cloudflare with the URL. For example if your freshrss instance is at https://freshrss.example.com/ and you want to subscribe Sebastian Ruder's excellent NLP newsletter [https://nlpnewsletter.substack.com/](https://nlpnewsletter.substack.com/), you would take the full URL to the RSS feed `https://nlpnewsletter.substack.com/feed` and set the feed url in FreshRSS to:

`https://freshrss.example.com/api/cloudsolver.php?feed=https://nlpnewsletter.substack.com/feed`

### viahtml parameter

The viahtml parameter should no longer be required - the script will work out how best to handle th edocument content

### Timeout

You can set the `maxTimeout` parameter in the control panel or by passing `maxTimeout=<miliseconds>` as a query parameter on the feed URL. The system will use whichever is the lower of these two values. The default value is 60 seconds. This allows you to have flaresolver run for longer than 60 seconds if needed.

## Upgrades to FreshRSS

If you are running FreshRSS as a docker image you will likely need to disable and reenable the plugin after you upgrade the main application. You can do this by toggling the extension using the switch in the control panel:

![image](https://github.com/user-attachments/assets/604ea7b3-c89e-4ba6-b2fe-27e57acae27c)

This is because the extension works by creating a file in the `/p/api/` directory of the application which is not persisted by the default linuxserver docker container. Hopefully I can find a way to fix this in future.

## Limitations

This plugin only works on exact URLs for RSS feeds at the moment. It can't be used to do feed discovery. This is due to a limitation with [the way that selenium works](https://github.com/FlareSolverr/FlareSolverr/blob/master/src/flaresolverr_service.py#L398).

## Contributing

If you have suggestions or encounter problems, feel free to open an issue. If you'd like to make code changes, submit a pull request!
