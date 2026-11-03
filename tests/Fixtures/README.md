# Media upload fixtures

- `video.mp4` and `video.mov`: four-second clips from [kodewright/dummy-data](https://github.com/kodewright/dummy-data), paths `videos/sample-video-landscape-480p.mp4` and `videos/sample-video-landscape-720p.mov`. The upstream MIT license is preserved in `LICENSE.dummy-data`; upstream documents the stock source provenance.
- `sample.mp4` and `sample-with-track.mp4`: minimal fixtures from [mathiasbynens/small](https://github.com/mathiasbynens/small), upstream files `Mpeg4.mp4` and `mp4-with-audio.mp4`. Used to prove that empty containers and audio-only files are not accepted as videos.
- Duration boundary tests patch the version-0 movie-header timing in memory; they do not depend on browser-supplied duration or a fake inspector.

These are parser/test assets, not CoffeeSpace user content.
