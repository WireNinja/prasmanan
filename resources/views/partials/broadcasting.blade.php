@auth
{{-- @TODO --}}
    @if(config('prasmanan.broadcasting.enabled', false))
        <script>
            function playSoundDefaultWav() {
                const audio = new Audio("{{ config('prasmanan.broadcasting.sound_url', '/sounds/default.wav') }}");
                audio.play();
            }

            window.addEventListener("DOMContentLoaded", function () {
                const userId = "{{ auth()->id() }}";
                const channelName = "{{ config('prasmanan.broadcasting.channel_prefix', 'App.Models.User.') }}" + userId;
                const eventName = "{{ config('prasmanan.broadcasting.event_name', 'Showcase\\\\SendWelcomeMessageEvent') }}";

                if (typeof window.Echo !== 'undefined') {
                    console.log("Prasmanan Broadcasting Setup Loaded");
                    window.Echo.private(channelName)
                        .listen(eventName, async (event) => {
                            playSoundDefaultWav();
                            new FilamentNotification()
                                .title(event.title || "Notification")
                                .body(event.message || "You have a new message.")
                                .icon(event.icon || "heroicon-o-document-text")
                                .iconColor(event.color || "success")
                                .send();
                        });
                } else {
                    console.warn("Laravel Echo is not defined. Ensure you have Echo loaded in your app.");
                }
            });
        </script>
    @endif
@endauth
