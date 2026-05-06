@php
  $renderHelpText = function ($text) {
    $safe = e((string) $text);
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
  <h2 class="help-topic-title">{{ $topic['title'] ?? 'Help Detail' }}</h2>

  @foreach(($topic['sections'] ?? []) as $section)
    <section class="help-topic-card">
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
