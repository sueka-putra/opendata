<section>
    <header class="mb-3">
        <h2 class="h6 mb-1 profile-section-title">Delete User</h2>
        <p class="mb-0 profile-section-subtitle">
            Permanently remove this user account.
        </p>
    </header>

    <button
        type="button"
        class="btn btn-danger"
        data-bs-toggle="modal"
        data-bs-target="#confirmUserDeletionModal"
    >
        Delete User
    </button>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmUserDeletionTitle">Delete this user?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="{{ route('profile.destroy') }}">
                    <div class="modal-body">
                        @csrf
                        @method('delete')

                        @if($targetUserId)
                            <input type="hidden" name="user_id" value="{{ $targetUserId }}">
                        @endif

                        <p class="small text-muted mb-3">
                            This action cannot be undone.
                        </p>

                        @if(!$isManagingOtherUser)
                            <label class="form-label profile-form-label" for="delete_password">Current Password</label>
                            <input
                                id="delete_password"
                                name="password"
                                type="password"
                                class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                                placeholder="Current password"
                            >
                            @if($errors->userDeletion->has('password'))
                                <div class="invalid-feedback d-block">{{ $errors->userDeletion->first('password') }}</div>
                            @endif
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn od-btn-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->userDeletion->isNotEmpty())
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('confirmUserDeletionModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        </script>
        @endpush
    @endif
</section>
