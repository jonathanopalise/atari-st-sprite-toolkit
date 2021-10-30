#include <inttypes.h>

typedef struct {
    uint16_t *address;
    int16_t horizon_ypos;
} ScreenBuffer;

void screen_buffers_init();
void screen_buffers_handle_vbl();
void screen_buffers_frame_complete();
ScreenBuffer *screen_buffers_get_drawing();

