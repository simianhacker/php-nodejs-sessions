# Sharing sessions between Node.js and PHP

**TL;DR** I'm working on a site to incrimentally migrate the codebase from PHP to Node.js. This is a working example of how to share session between PHP and Node.js using Redis as the session store.

## Background

I recently decided to migrate a codebase I've been working on from PHP to Node.js. Normally I would try and just do a wholesale rewrite but since this project is pretty extnesive, we need to do our upgrades incrementally. 

The idea is to write all our new features using Express + Backbone.js and proxy requests the Express application can't handle to Apache/PHP. This will allow us to continue serving the existing features and start building all our new features using Node.js (along with migrating features as we have time).
