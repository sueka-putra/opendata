<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Open Data - Help Center</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="/img/opendata.png">
  <style>
    body {
      margin: 0;
      background: linear-gradient(160deg, #eef5ff 0%, #e3eeff 50%, #d6e7ff 100%);
      color: #1d2f4f;
      font-size: 14px;
    }
    .help-shell {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1rem;
    }
    .help-head {
      background: linear-gradient(135deg, #0f4ca0, #1e67c8);
      color: #fff;
      border-radius: 12px;
      padding: 1rem 1.1rem;
      box-shadow: 0 10px 25px rgba(12, 67, 150, 0.22);
      margin-bottom: 0.9rem;
    }
    .help-head h1 {
      margin: 0;
      font-size: 1.15rem;
    }
    .help-head p {
      margin: 0.35rem 0 0;
      opacity: 0.95;
    }
    .help-layout {
      display: grid;
      grid-template-columns: 290px minmax(0, 1fr);
      gap: 0.8rem;
      min-height: calc(100vh - 160px);
    }
    .help-topics,
    .help-content {
      background: #fff;
      border-radius: 10px;
      border: 1px solid #c9dbfb;
      box-shadow: 0 8px 20px rgba(45, 98, 181, 0.12);
    }
    .help-topics {
      padding: 0.65rem;
      overflow: auto;
    }
    .help-topics h2 {
      margin: 0 0 0.55rem;
      font-size: 0.9rem;
      color: #0f4ca0;
      font-weight: 700;
    }
    .help-topic-group {
      margin-bottom: 0.5rem;
    }
    .help-topic-parent {
      font-size: 0.87rem;
      font-weight: 700;
      color: #1a3f78;
      margin: 0.15rem 0 0.25rem;
    }
    .help-topic-children {
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .help-topic-link {
      display: block;
      padding: 0.25rem 0.25rem;
      border-radius: 7px;
      border: 1px solid transparent;
      background: transparent;
      color: #204b8f;
      text-decoration: none;
      margin: 0.05rem 0 0.18rem 0.7rem;
      font-weight: 600;
      font-size: 0.86rem;
      width: calc(100% - 0.7rem);
      box-sizing: border-box;
    }
    .help-topic-link:hover {
      color: #123f82;
      text-decoration: underline;
      text-underline-offset: 3px;
    }
    .help-topic-link.is-active {
      background: #edf4ff;
      border-color: transparent;
      color: #123c7a;
      padding: 0.35rem 0.6rem;
    }
    .help-content {
      padding: 0.95rem 1rem;
      overflow: auto;
    }
    .loading-text {
      color: #4d6ea3;
    }
    .help-topic-title {
      color: #17488f;
      font-weight: 800;
      margin-bottom: 0.75rem;
      letter-spacing: 0.01em;
    }
    .help-topic-header {
      border-bottom: 1px solid #dbe7fb;
      padding-bottom: 0.65rem;
      margin-bottom: 0.9rem;
    }
    .help-topic-summary {
      color: #365278;
      font-size: 0.92rem;
      line-height: 1.45;
      margin: 0.35rem 0 0;
    }
    .help-topic-section {
      margin-bottom: 1rem;
    }
    .help-topic-section:not(:last-child) {
      padding-bottom: 0.95rem;
      border-bottom: 1px solid #e3edfc;
    }
    .help-topic-section h3 {
      color: #17488f;
      font-size: 1rem;
      margin: 0 0 0.45rem;
      font-weight: 700;
    }
    .help-topic-section p,
    .help-topic-section li {
      color: #1f2e45;
      font-size: 0.93rem;
      line-height: 1.5;
    }
    .help-topic-section ul {
      margin-bottom: 0.35rem;
      padding-left: 1.1rem;
    }
    .help-label-strong {
      color: #123f82;
      font-weight: 700;
    }
    .help-score-chip {
      display: inline-block;
      padding: 0.08rem 0.42rem;
      border-radius: 999px;
      border: 1px solid #9ec0ee;
      background: linear-gradient(180deg, #f1f7ff 0%, #e4efff 100%);
      color: #0f4ca0;
      font-weight: 700;
      font-size: 0.82em;
      line-height: 1.2;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      vertical-align: baseline;
    }
    .help-inline-code {
      background: #edf4ff;
      color: #0f4ca0;
      border: 1px solid #c6dbfb;
      border-radius: 0.35rem;
      padding: 0.06rem 0.3rem;
      font-size: 0.82em;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    @media (max-width: 900px) {
      .help-layout {
        grid-template-columns: 1fr;
        min-height: auto;
      }
    }
  </style>
</head>
<body>
  <div class="help-shell">
    <header class="help-head">
      <h1>Open Data Help Center</h1>
    </header>

    <div class="help-layout">
      <aside class="help-topics">
        <h2>Help Topics</h2>
        @foreach(($topicGroups ?? []) as $group)
          <div class="help-topic-group">
            <div class="help-topic-parent">{{ $group['title'] ?? '' }}</div>
            <ul class="help-topic-children">
              @foreach(($group['children'] ?? []) as $child)
                <li>
                  <a
                    href="{{ route('help', ['topic' => $child['key']]) }}"
                    class="help-topic-link{{ $activeTopic === ($child['key'] ?? '') ? ' is-active' : '' }}"
                    data-topic-key="{{ $child['key'] ?? '' }}"
                  >{{ $child['title'] ?? '' }}</a>
                </li>
              @endforeach
            </ul>
          </div>
        @endforeach
      </aside>

      <main class="help-content" id="helpTopicContent">
        <div class="loading-text">Loading topic detail...</div>
      </main>
    </div>
  </div>

  <script>
    const baseUrl = @json(route('help'));
    const contentEl = document.getElementById('helpTopicContent');
    const links = [...document.querySelectorAll('.help-topic-link')];

    async function loadTopic(topicKey, pushState = true) {
      if (!topicKey || !contentEl) return;
      links.forEach((lnk) => lnk.classList.toggle('is-active', lnk.dataset.topicKey === topicKey));
      contentEl.innerHTML = '<div class="loading-text">Loading topic detail...</div>';

      const url = `${baseUrl}?topic=${encodeURIComponent(topicKey)}&partial=1`;
      const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) {
        contentEl.innerHTML = '<div class="text-danger">Failed to load topic detail.</div>';
        return;
      }
      contentEl.innerHTML = await response.text();

      if (pushState) {
        const stateUrl = `${baseUrl}?topic=${encodeURIComponent(topicKey)}`;
        window.history.pushState({ topic: topicKey }, '', stateUrl);
      }
    }

    links.forEach((lnk) => {
      lnk.addEventListener('click', (event) => {
        event.preventDefault();
        loadTopic(lnk.dataset.topicKey, true);
      });
    });

    window.addEventListener('popstate', () => {
      const params = new URLSearchParams(window.location.search);
      const topic = params.get('topic') || @json($activeTopic);
      loadTopic(topic, false);
    });

    loadTopic(@json($activeTopic), false);
  </script>
</body>
</html>
