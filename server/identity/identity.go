package identity

// ConnectionID 连接id
type ConnectionID uint32

// ObjectID bitmap对象id
type ObjectID uint32

type ID struct {
	ConnectionID `json:"connectionID"`
	ObjectID     `json:"objectID"`
}
