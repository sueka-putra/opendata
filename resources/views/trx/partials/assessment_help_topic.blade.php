@php
  $renderHelpText = function ($text) {
    $safe = e((string) $text);
    $safe = preg_replace_callback('/(\/templates\/[A-Za-z0-9._\-\/ ]+\.(?:xlsx|xls|csv|json|xml|txt))/i', function ($m) {
      $label = $m[1];
      $href = str_replace(' ', '%20', $label);
      return '<a href="' . $href . '" target="_blank" rel="noopener">' . $label . '</a>';
    }, $safe);
    $safe = preg_replace('/\b(0\.5|0|1)\b/', '<span class="help-score-chip">$1</span>', $safe);
    $safe = preg_replace('/\b(NA|XLSX|CSV|JSON|XML|TXT|API)\b/', '<span class="help-inline-code">$1</span>', $safe);
    return $safe;
  };

  $renderBullet = function ($text) use ($renderHelpText) {
    $raw = (string) $text;
    if (str_contains($raw, ':')) {
      [$head, $tail] = explode(':', $raw, 2);
      return '<span class="help-label-strong">' . e(trim($head)) . ':</span> ' . $renderHelpText(trim($tail));
    }
    return $renderHelpText($raw);
  };
@endphp

<article>
  <header class="help-topic-header">
    <h2 class="help-topic-title">{{ $topic['title'] ?? 'Help Detail' }}</h2>
    @if(!empty($topic['description']))
      <p class="help-topic-summary">{!! $renderHelpText($topic['description']) !!}</p>
    @endif
    @if(!empty($topic['note_html']))
      <p class="help-topic-summary mb-0">{!! $topic['note_html'] !!}</p>
    @elseif(!empty($topic['note']))
      <p class="help-topic-summary mb-0">{!! $renderHelpText($topic['note']) !!}</p>
    @endif
  </header>

  @foreach(($topic['sections'] ?? []) as $section)
    <section class="help-topic-section">
      <h3>{{ $section['heading'] ?? '' }}</h3>

      @foreach(($section['paragraphs'] ?? []) as $paragraph)
        <p class="mb-2">{!! $renderHelpText($paragraph) !!}</p>
      @endforeach

      @if(!empty($section['bullets']))
        <ul class="mb-2">
          @foreach($section['bullets'] as $bullet)
            <li>{!! $renderBullet($bullet) !!}</li>
          @endforeach
        </ul>
      @endif
    </section>
  @endforeach
</article>
