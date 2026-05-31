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
  @if(!empty($topic['custom_view']))
    @include($topic['custom_view'])
  @else
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
      @if(!empty($section['image']))
        <div class="mb-3">
          <img
            src="{{ asset(ltrim((string) $section['image'], '/')) }}"
            alt="{{ $section['heading'] ?? 'Help image' }}"
            class="img-fluid rounded border"
            style="border-color:#dbe7fb !important;"
          >
        </div>
      @endif
      <h3>{{ $section['heading'] ?? '' }}</h3>

      @foreach(($section['paragraphs'] ?? []) as $paragraph)
        <p class="mb-2">{!! $renderHelpText($paragraph) !!}</p>
        @if(
          !empty($section['image_after_paragraph']) &&
          (int) ($section['image_after_paragraph_index'] ?? 1) === ($loop->index + 1)
        )
          @php
            $imageAfterParagraphWidth = trim((string) ($section['image_after_paragraph_width'] ?? '100%'));
          @endphp
          <div class="mt-2 mb-3">
            <img
              src="{{ asset(ltrim((string) $section['image_after_paragraph'], '/')) }}"
              alt="{{ ($section['heading'] ?? 'Help section') . ' image' }}"
              class="img-fluid rounded border"
              style="border-color:#dbe7fb !important; width: {{ $imageAfterParagraphWidth }}; max-width: {{ $imageAfterParagraphWidth }};"
            >
          </div>
        @endif
      @endforeach

      @if(!empty($section['bullets']))
        <ul class="mb-2">
          @foreach($section['bullets'] as $bullet)
            <li>{!! $renderBullet($bullet) !!}</li>
          @endforeach
        </ul>
      @endif

      @if(!empty($section['image_after']))
        @php
          $afterWidth = trim((string) ($section['image_after_width'] ?? '100%'));
        @endphp
        <div class="mt-3">
          <img
            src="{{ asset(ltrim((string) $section['image_after'], '/')) }}"
            alt="{{ ($section['heading'] ?? 'Help section') . ' image' }}"
            class="img-fluid rounded border"
            style="border-color:#dbe7fb !important; width: {{ $afterWidth }}; max-width: {{ $afterWidth }};"
          >
        </div>
      @endif

      @if(!empty($section['image_after_list']) && is_array($section['image_after_list']))
        @foreach($section['image_after_list'] as $img)
          @php
            $afterListSrc = is_array($img) ? ($img['src'] ?? '') : $img;
            $afterListWidth = trim((string) (is_array($img) ? ($img['width'] ?? '100%') : '100%'));
          @endphp
          @if(!empty($afterListSrc))
            <div class="mt-3">
              <img
                src="{{ asset(ltrim((string) $afterListSrc, '/')) }}"
                alt="{{ ($section['heading'] ?? 'Help section') . ' image' }}"
                class="img-fluid rounded border"
                style="border-color:#dbe7fb !important; width: {{ $afterListWidth }}; max-width: {{ $afterListWidth }};"
              >
            </div>
          @endif
        @endforeach
      @endif
    </section>
  @endforeach
  @endif
</article>
