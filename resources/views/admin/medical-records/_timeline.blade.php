{{-- Timeline of records. Params: $records (collection or paginator), $showPet (bool), $branch --}}
@php
    $items = $records instanceof \Illuminate\Pagination\AbstractPaginator ? $records->getCollection() : collect($records);
    $grouped = $items->groupBy(fn ($r) => $r->consultation_date?->format('F Y') ?? '—');
    $today = \Carbon\Carbon::today();
@endphp

@foreach($grouped as $month => $group)
    <div class="tl-month">{{ $month }}</div>
    <div class="tl">
        @foreach($group as $r)
            @php
                $pet = $r->pet;
                $seenAt = $pet ? $pet->appointments->pluck('branch')->filter()->unique()->values() : collect();
                $vaxList = $r->vaccinations
                    ? array_values(array_filter(array_map('trim', preg_split('/[,;\n\r]+/', $r->vaccinations))))
                    : [];
            @endphp
            <div class="tl-item">
                <div class="tl-date">
                    <div class="d">{{ $r->consultation_date?->format('d') ?? '—' }}</div>
                    <div class="m">{{ $r->consultation_date?->format('M') ?? '—' }}</div>
                </div>
                <div class="tl-card" onclick="window.location='{{ route('admin.medical-records.show', $r->id) }}'">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="selected_ids[]" value="{{ $r->id }}" class="row-checkbox"
                                   style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59; flex-shrink: 0;"
                                   title="Select record" onclick="event.stopPropagation()">
                            @if($showPet && $pet)
                                @include('admin.medical-records._avatar', ['pet' => $pet, 'size' => 44])
                                <div>
                                    <a href="{{ route('admin.medical-records.pet', $pet->id) }}" class="tl-pet-name"
                                       onclick="event.stopPropagation()">{{ $pet->name }}</a>
                                    <div class="tl-sub">
                                        {{ $pet->species }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}
                                        &middot; Owner: {{ $pet->owner->name ?? 'Unknown' }}
                                    </div>
                                </div>
                            @else
                                <div class="tl-sub" style="font-size: 13px; color: #2E3230; font-weight: 600;">
                                    {{ $r->consultation_date?->format('l, F j, Y') ?? '—' }}
                                </div>
                            @endif
                        </div>
                        @if(!empty($r->veterinarian) && $r->veterinarian !== 'Clinic Staff')
                            <div class="tl-sub text-end">
                                <i class="fas fa-user-doctor me-1" style="color: #4A7C59;"></i>{{ $r->veterinarian }}
                            </div>
                        @endif
                    </div>

                    <div class="tl-diagnosis">
                        <span class="lbl">Diagnosis</span>
                        {{ $r->diagnosis ? \Illuminate\Support\Str::limit($r->diagnosis, 150) : 'No diagnosis recorded' }}
                    </div>

                    @if(count($vaxList))
                        <div class="mb-2">
                            @foreach(array_slice($vaxList, 0, 4) as $vx)
                                <span class="vax-pill ok" style="font-size: 11px; padding: 3px 10px; margin-bottom: 4px;">
                                    <i class="fas fa-syringe"></i>{{ $vx }}
                                </span>
                            @endforeach
                            @if(count($vaxList) > 4)
                                <span class="tl-sub">+{{ count($vaxList) - 4 }} more</span>
                            @endif
                        </div>
                    @endif

                    <div class="tl-foot">
                        <div>
                            @if($r->next_visit_date)
                                <span class="next-pill {{ $r->next_visit_date->gte($today) ? 'upcoming' : 'past' }}">
                                    <i class="fas fa-calendar-day"></i>
                                    Next visit {{ $r->next_visit_date->format('M d, Y') }}
                                </span>
                            @endif
                            @if($showPet)
                                @foreach($seenAt as $b)
                                    <span class="branch-chip {{ ($branch ?? null) && $b === $branch ? 'mine' : '' }}">
                                        <i class="fas fa-location-dot"></i>{{ \Illuminate\Support\Str::before($b, ' Branch') }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.medical-records.show', $r->id) }}" class="tl-link"
                               onclick="event.stopPropagation()">View details <i class="fas fa-arrow-right ms-1"></i></a>
                            <button type="button" class="btn btn-sm text-danger p-1"
                                    title="Delete record"
                                    onclick="event.stopPropagation();"
                                    data-bs-toggle="modal" data-bs-target="#deleteMrModal-{{ $r->id }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade text-start" id="deleteMrModal-{{ $r->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation();">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border-radius: 24px; border: none;">
                                <form method="POST" action="{{ route('admin.medical-records.destroy', $r->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect_to" value="{{ $showPet ? 'index' : 'pet' }}">
                                    <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                                        <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                                            <i class="fas fa-trash-alt me-2"></i> Delete Medical Record
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                                        <p class="mb-2">Are you sure you want to delete this medical record from <strong>{{ $r->consultation_date?->format('M d, Y') ?? '—' }}</strong>?</p>
                                        <div class="p-2 rounded mt-2" style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.25); color: #1B5E20; font-size: 12px;">
                                            <i class="fas fa-shield-alt me-1"></i> <strong>Safe Deletion:</strong> The pet profile and appointments will remain completely untouched.
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #BA1A1A; border: none;">
                                            Delete Record
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
