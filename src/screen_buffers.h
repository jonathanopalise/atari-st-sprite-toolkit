#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>

typedef struct {
    uint16_t *address;
    int16_t horizon_ypos;
} ScreenBuffer;

void screen_buffers_init();
void screen_buffers_handle_vbl();
void screen_buffers_frame_complete();
uint16_t *screen_buffers_get_drawing_address();

